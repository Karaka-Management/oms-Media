<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use phpOMS\DataStorage\Database\RelationType;
use Modules\Admin\Models\Account;

/**
 * Mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class CollectionMapper extends MediaMapper
{
    /**
     * Has many relation.
     *
     * @var array<string, array{mapper:string, table:string, self?:?string, external?:?string, column?:string}>
     * @since 1.0.0
     */
    protected static array $hasMany = [
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
     * @var string
     * @since 1.0.0
     */
    protected static string $model = Collection::class;

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $table = 'media';

    /**
     * Created at.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $createdAt = 'media_created_at';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $primaryField = 'media_id';

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
     * in a different virtual path and are therfore similar to "symlinks", except that they don't reference
     * a file but create a new virtual media model which groups other media models together in a new virtual
     * path if so desired without deleting or moving the orginal media files.
     *
     * @param string $virtualPath Virtual path
     * @param bool   $hidden      Get hidden files
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function getByVirtualPath(string $virtualPath = '/', bool $hidden = false) : array
    {
        $depth = 3;
        $query = self::getQuery();
        $query->where(self::$table . '_' . $depth . '.media_virtual', '=', $virtualPath);
        $query->where(self::$table . '_' . $depth . '.media_collection', '=', 1);

        if ($hidden === false) {
            $query->andWhere(self::$table . '_' . $depth . '.media_hidden', '=', (int) $hidden);
        }

        return self::getAllByQuery($query, RelationType::ALL, $depth);
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
    public static function getCollectionsByPath(string $path, bool $showDirectories = false) : array
    {
        $collection = CollectionMapper::getByVirtualPath($path);
        $parent     = [];

        if ($showDirectories) {
            $parent = CollectionMapper::getParentCollection($path);
            if (\is_array($parent) && \is_dir(__DIR__ . '/../../Media/Files' . $path)) {
                $parent = new Collection();
                $parent->name = \basename($path);
                $parent->setVirtualPath(\dirname($path));
                $parent->setPath(\dirname($path));
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

                    $localMedia = new Collection();
                    $localMedia->name = $pathinfo['filename'];
                    $localMedia->extension = \is_dir($file) ? 'collection' : $pathinfo['extension'] ?? '';
                    $localMedia->setVirtualPath($path);
                    $localMedia->createdBy = new Account();

                    $collection[] = $localMedia;
                }
            }
        }

        return [$collection, $parent];
    }
}
