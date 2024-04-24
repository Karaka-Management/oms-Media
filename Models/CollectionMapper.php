<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use Modules\Admin\Models\Account;
use phpOMS\DataStorage\Database\Mapper\ReadMapper;

/**
 * Collection mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 *
 * @template T of Collection
 * @extends MediaMapper<T>
 */
final class CollectionMapper extends MediaMapper
{
    /**
     * Has many relation.
     *
     * @var array<string, array{mapper:class-string, table:string, self?:?string, external?:?string, column?:string}>
     * @since 1.0.0
     */
    public const HAS_MANY = [
        'sources' => [
            'mapper'   => MediaMapper::class,
            'table'    => 'media_relation',
            'external' => 'media_relation_src',
            'self'     => 'media_relation_dst',
        ],
    ];

    /**
     * Model to use by the mapper.
     *
     * @var class-string<T>
     * @since 1.0.0
     */
    public const MODEL = Collection::class;

    /**
     * Get media based on virtual path.
     *
     * The virtual path is equivalent to the directory path on a file system.
     *
     * A media model also has a file path, this however doesn't have to be the same as the virtual path
     * and in fact most of the time it is different. This is because the location on a hard drive or web
     * drive should not have any impact on the media file/media structure in the application.
     *
     * As a result media files are structured by virutal path in the app, by file path on the file system
     * and by Collections which can have sub-collections as well. Collections allow to reference files
     * in a different virtual path and are therefore similar to "symlinks", except that they don't reference
     * a file but create a new virtual media model which groups other media models together in a new virtual
     * path if so desired without deleting or moving the orginal media files.
     *
     * @param string $virtualPath Virtual path
     * @param int    $status      Media status
     *
     * @return ReadMapper
     *
     * @since 1.0.0
     */
    public static function getByVirtualPath(string $virtualPath = '/', int $status = MediaStatus::NORMAL) : ReadMapper
    {
        return self::getAll()
            ->where('virtualPath', $virtualPath)
            ->with('createdBy')
            ->with('tags')
            ->where('status', $status)
            ->where('class', MediaClass::COLLECTION);
    }

    /**
     * Get collections and optionally hard drive directories.
     *
     * @param string $virtualPath     Virtual path
     * @param bool   $showDirectories Show local hard drive directories
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function getCollectionsByPath(string $virtualPath, bool $showDirectories = false) : array
    {
        /** @var Media[] $collection */
        $collection = self::getByVirtualPath($virtualPath)->with('sources')->execute();
        $parent     = [];

        if ($showDirectories) {
            $parent = self::getParentCollection($virtualPath)->execute();
            if (\is_array($parent) && \is_dir(__DIR__ . '/../../Media/Files' . $virtualPath)) {
                $parent       = new Collection();
                $parent->name = \basename($virtualPath);
                $parent->setVirtualPath(\dirname($virtualPath));
                $parent->setPath(\dirname($virtualPath));
                $parent->isAbsolute = false;
            }

            if ($parent instanceof Collection) {
                $collection += $parent->getSources();

                /** @var string[] $glob */
                $glob = $parent->isAbsolute
                    ? $parent->getPath() . '/' . $parent->name . '/*'
                    : \glob(__DIR__ . '/../Files/' . \rtrim($parent->getPath(), '/') . '/' . $parent->name . '/*');
                $glob = $glob === false ? [] : $glob;

                foreach ($glob as $file) {
                    if (!\is_dir($file)) {
                        continue;
                    }

                    foreach ($collection as $obj) {
                        if (($obj->extension !== 'collection'
                                && !empty($obj->extension)
                                && $obj->name . '.' . $obj->extension === \basename($file))
                            || ($obj->extension === 'collection'
                                && $obj->name === \basename($file))
                        ) {
                            continue 2;
                        }
                    }

                    $pathinfo = \pathinfo($file);

                    $localMedia            = new Collection();
                    $localMedia->name      = $pathinfo['filename'];
                    $localMedia->extension = $pathinfo['extension'] ?? '';
                    $localMedia->setVirtualPath($virtualPath);
                    $localMedia->createdBy = new Account();

                    $collection[] = $localMedia;
                }
            }
        }

        return [$collection, $parent];
    }
}
