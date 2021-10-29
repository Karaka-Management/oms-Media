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

use Modules\Admin\Models\AccountMapper;
use Modules\Admin\Models\NullAccount;
use Modules\Media\Controller\ApiController;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\MediaType;
use Modules\Media\Models\MediaTypeL11n;
use Modules\Media\Models\MediaTypeL11nMapper;
use Modules\Media\Models\MediaTypeMapper;
use Modules\Media\Models\UploadFile;
use phpOMS\Application\ApplicationAbstract;
use phpOMS\Config\SettingsInterface;
use phpOMS\DataStorage\Database\DatabasePool;
use phpOMS\Module\InstallerAbstract;
use phpOMS\Module\ModuleInfo;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\File\Local\File;
use phpOMS\System\File\PathException;

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
     * Path of the file
     *
     * @var string
     * @since 1.0.0
     */
    public const PATH = __DIR__;

    /**
     * {@inheritdoc}
     */
    public static function install(DatabasePool $dbPool, ModuleInfo $info, SettingsInterface $cfgHandler) : void
    {
        if (!\is_dir(__DIR__ . '/../Files')) {
            \mkdir(__DIR__ . '/../Files');
        }

        parent::install($dbPool, $info, $cfgHandler);

        // Create directory for admin account
        // All other accounts are automatically created in the admin module whenever they get created
        // However, the admin account is created before the Media module is installed
        // Because of this, the directory needs to be created manually after the Media installation
        // The admin account should be the only DB account, but we use a loop of all accounts to avoid bugs
        $accounts = AccountMapper::getAll();

        foreach ($accounts as $account) {
            $collection       = new Collection();
            $collection->name = ((string) $account->getId()) . ' ' . $account->login;
            $collection->setVirtualPath('/Accounts');
            $collection->setPath('/Modules/Media/Files/Accounts/' . ((string) $account->getId()));
            // The installation is always run by the admin account since the module is a "base" module which is always installed during the application setup
            $collection->createdBy = new NullAccount(1);

            CollectionMapper::create($collection);
        }
    }

    /**
     * Install data from providing modules.
     *
     * The data can be either directories which should be created or files which should be "uploaded"
     *
     * @param ApplicationAbstract $app  Application
     * @param array               $data Additional data
     *
     * @return array
     *
     * @throws PathException
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public static function installExternal(ApplicationAbstract $app, array $data) : array
    {
        try {
            $app->dbPool->get()->con->query('select 1 from `media`');
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
            'upload'     => [],
            'type'       => [],
        ];

        \mkdir(__DIR__ . '/tmp');
        foreach ($mediaData as $media) {
            switch ($media['type']) {
                case 'collection':
                    $result['collection'][] = self::createCollection($app->dbPool, $media);
                    break;
                case 'upload':
                    $result['upload'][] = self::uploadMedia($app->dbPool, $media);
                    break;
                case 'type':
                    $result['type'][] = self::createType($app->dbPool, $media);
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
    private static function createCollection(DatabasePool $dbPool, array $data) : Collection
    {
        if (!isset($data['path'])) {
            $dirPath = __DIR__ . '/../../../Modules/Media/Files' . ($data['virtualPath'] ?? '/') . '/' . ($data['name'] ?? '');
            $path    = '/Modules/Media/Files' . ($data['virtualPath'] ?? '') . '/' . ($data['name'] ?? '');
        } else {
            $dirPath = $data['path'] . '/' . ($data['name'] ?? '');
            $path    = $data['path'] ?? '/Modules/Media/Files/' . ($data['name'] ?? '');
        }

        $collection       = new Collection();
        $collection->name = $data['name'] ?? '';
        $collection->setVirtualPath($data['virtualPath'] ?? '/');
        $collection->setPath($path);
        $collection->createdBy = new NullAccount((int) $data['user'] ?? 1);

        CollectionMapper::create($collection);

        if ($data['create_directory'] && !\is_dir($dirPath)) {
            // @todo fix permission mode
            \mkdir($dirPath, 0755, true);
        }

        return $collection;
    }

    /**
     * Create type.
     *
     * @param DatabasePool $dbPool Database instance
     * @param array        $data   Media info
     *
     * @return MediaType
     *
     * @since 1.0.0
     */
    private static function createType(DatabasePool $dbPool, array $data) : MediaType
    {
        $type       = new MediaType();
        $type->name = $data['name'] ?? '';

        $id = MediaTypeMapper::create($type);

        foreach ($data['l11n'] as $l11n) {
            $l11n       = new MediaTypeL11n($l11n['title'], $l11n['lang']);
            $l11n->type = $id;

            MediaTypeL11nMapper::create($l11n);
        }

        return $type;
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
    private static function uploadMedia(DatabasePool $dbPool, array $data) : array
    {
        $files = [];
        foreach ($data['files'] as $file) {
            if (\is_file(__DIR__ . '/../../..' . $file)) {
                File::copy(__DIR__ . '/../../..' . $file, __DIR__ . '/tmp' . $file);

                $files[] = [
                    'size'     => \filesize(__DIR__ . '/tmp' . $file),
                    'name'     => \basename($file),
                    'tmp_name' => __DIR__ . '/tmp' . $file,
                    'error'    => \UPLOAD_ERR_OK,
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
                        'size'     => \filesize($item->getPathname()),
                        'name'     => \basename($item->getPathname()),
                        'tmp_name' => $item->getPathname(),
                        'error'    => \UPLOAD_ERR_OK,
                    ];
                }
            }
        }

        $upload                   = new UploadFile();
        $upload->preserveFileName = $data['fixed_names'] ?? true;
        $upload->outputDir        = empty($data['path'] ?? '')
            ? ApiController::createMediaPath()
            : __DIR__ . '/../../..' . $data['path'];

        $status = $upload->upload($files, ($data['fixed_names'] ?? true) ? [] : [$data['name']], true);

        $mediaFiles = [];
        foreach ($status as $uFile) {
            $media = new Media();

            $media->setPath(ApiController::normalizeDbPath($data['path']) . '/' . $uFile['filename']);
            $media->name      = !empty($uFile['name']) ? $uFile['name'] : $uFile['filename'];
            $media->size      = $uFile['size'];
            $media->createdBy = new NullAccount((int) $data['user'] ?? 1);
            $media->extension = $uFile['extension'];
            $media->setVirtualPath((string) ($data['virtualPath'] ?? '/'));
            $media->type = $data['media_type'] ?? null; // = identifier for modules

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
