<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media\Admin
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\Admin;

use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use phpOMS\DataStorage\Database\DatabasePool;
use phpOMS\Module\InstallerAbstract;
use phpOMS\System\File\PathException;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\File\Local\File;
use Modules\Media\Models\UploadFile;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Controller\ApiController;

/**
 * Installer class.
 *
 * @package Modules\Media\Admin
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class Installer extends InstallerAbstract
{
    /**
     * Install data from providing modules.
     *
     * @param DatabasePool $dbPool Database pool
     * @param array        $data   Module info
     *
     * @return array
     *
     * @throws PathException This exception is thrown if the Navigation install file couldn't be found
     * @throws \Exception    This exception is thrown if the Navigation install file is invalid json
     *
     * @since 1.0.0
     */
    public static function installExternal(DatabasePool $dbPool, array $data) : array
    {
        try {
            $dbPool->get()->con->query('select 1 from `media`');
        } catch (\Exception $e) {
            return []; // @codeCoverageIgnore
        }

        if (!\is_file($data['path'] ?? '')) {
            throw new PathException($data['path'] ?? '');
        }

        $mediaFile = \file_get_contents($data['path'] ?? '');
        if ($mediaFile === false) {
            throw new PathException($data['path'] ?? ''); // @codeCoverageIgnore
        }

        $mediaData = \json_decode($mediaFile, true) ?? [];
        if ($mediaData === false) {
            throw new \Exception(); // @codeCoverageIgnore
        }

        if (\is_dir(__DIR__ . '/tmp')) {
            Directory::delete(__DIR__ . '/tmp');
        }

        $result = [
            'collection' => [],
            'upload' => [],
        ];

        \mkdir(__DIR__ . '/tmp');
        foreach ($mediaData as $media) {
            switch ($media['type']) {
                case 'collection':
                    $result['collection'][] = self::createCollection($dbPool, $media);
                    break;
                case 'upload':
                    $result['upload'][] = self::uploadMedia($dbPool, $media);
                    break;
                default:
            }
        }
        Directory::delete(__DIR__ . '/tmp');

        return $result;
    }

    /**
     * Create collection.
     *
     * @param DatabasePool $dbPool Database instance
     * @param array        $data   Media info
     *
     * @return Collection
     *
     * @since 1.0.0
     */
    private static function createCollection($dbPool, $data) : Collection
    {
        $collection       = new Collection();
        $collection->name = (string) $data['name'] ?? '';
        $collection->setVirtualPath((string) $data['virtualPath'] ?? '/');
        $collection->setPath((string) ($data['path'] ?? '/Modules/Media/Files/' . ((string) $data['name'] ?? '')));
        $collection->createdBy = new NullAccount((int) $data['user'] ?? 1);

        CollectionMapper::create($collection);
    }

    /**
     * Upload media.
     *
     * @param DatabasePool $dbPool Database instance
     * @param array        $data   Media info
     *
     * @return array
     *
     * @since 1.0.0
     */
    private static function uploadMedia($dbPool, $data) : array
    {
        $files = [];
        foreach ($data['files'] as $file) {
            if (\is_file(__DIR__ . '/../../..' . $file)) {
                File::copy(__DIR__ . '/../../..' . $file, __DIR__ . '/tmp' . $file);

                $files[] = [
                    'size' => \filesize(__DIR__ . '/tmp' . $file),
                    'name' => \basename($file),
                    'tmp_name' => __DIR__ . '/tmp' . $file,
                    'error' => \UPLOAD_ERR_OK,
                ];
            } if (\is_dir(__DIR__ . '/../../..' . $file)) {
                Directory::copy(__DIR__ . '/../../..' . $file, __DIR__ . '/tmp' . $file);

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(__DIR__ . '/tmp' . $file . '/', \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        continue;
                    }

                    $files[] = [
                        'size' => \filesize($item->getPathname()),
                        'name' => \basename($item->getPathname()),
                        'tmp_name' => $item->getPathname(),
                        'error' => \UPLOAD_ERR_OK,
                    ];
                }
            }
        }

        $upload = new UploadFile();
        $upload->setOutputDir(empty($data['path'] ?? '') ? ApiController::createMediaPath() : __DIR__ . '/../../..' . $data['path']);

        $status = $upload->upload($files, $data['name'], true);

        $mediaFiles = [];
        foreach ($status as $uFile) {
            $media = new Media();

            $media->setPath(ApiController::normalizeDbPath($data['path']) . '/' . $uFile['filename']);
            $media->name      = $uFile['name'];
            $media->size      = $uFile['size'];
            $media->createdBy = new NullAccount((int) $data['user'] ?? 1);
            $media->extension = $uFile['extension'];
            $media->setVirtualPath((string) ($data['virtualPath'] ?? '/') . '/' . $data['name']);
            $media->type = $data['type'] ?? ''; // = identifier for modules

            MediaMapper::create($media);

            $mediaFiles[] = $media;
        }

        if ($data['create_collection']) {
            $collection       = new Collection();
            $collection->name = (string) $data['name'] ?? '';
            $collection->setVirtualPath((string) $data['virtualPath'] ?? '/');
            $collection->setPath((string) ($data['path'] ?? '/Modules/Media/Files/' . ((string) $data['name'] ?? '')));
            $collection->createdBy = new NullAccount((int) $data['user'] ?? 1);

            $collection->setSources($mediaFiles);

            CollectionMapper::create($collection);
            return [$collection];
        }

        return $mediaFiles;
    }
}
