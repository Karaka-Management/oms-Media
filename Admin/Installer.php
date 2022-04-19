<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media\Admin
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Admin;

use Modules\Admin\Models\AccountMapper;
use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\MediaType;
use Modules\Media\Models\PathSettings;
use phpOMS\Application\ApplicationAbstract;
use phpOMS\Config\SettingsInterface;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\HttpResponse;
use phpOMS\Module\InstallerAbstract;
use phpOMS\Module\ModuleInfo;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\File\Local\File;
use phpOMS\System\File\PathException;
use phpOMS\Uri\HttpUri;

/**
 * Installer class.
 *
 * @package Modules\Media\Admin
 * @license OMS License 1.0
 * @link    https://karaka.app
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
    public static function install(ApplicationAbstract $app, ModuleInfo $info, SettingsInterface $cfgHandler) : void
    {
        if (!\is_dir(__DIR__ . '/../Files')) {
            \mkdir(__DIR__ . '/../Files');
        }

        parent::install($app, $info, $cfgHandler);

        // Create directory for admin account
        // All other accounts are automatically created in the admin module whenever they get created
        // However, the admin account is created before the Media module is installed
        // Because of this, the directory needs to be created manually after the Media installation
        // The admin account should be the only DB account, but we use a loop of all accounts to avoid bugs
        $accounts = AccountMapper::getAll()->execute();

        foreach ($accounts as $account) {
            $collection       = new Collection();
            $collection->name = ((string) $account->getId()) . ' ' . $account->login;
            $collection->setVirtualPath('/Accounts');
            $collection->setPath('/Modules/Media/Files/Accounts/' . ((string) $account->getId()));
            // The installation is always run by the admin account since the module is a "base" module which is always installed during the application setup
            $collection->createdBy = new NullAccount(1);

            CollectionMapper::create()->execute($collection);
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

        $apiApp = new class() extends ApplicationAbstract
        {
            protected string $appName = 'Api';
        };

        $apiApp->dbPool         = $app->dbPool;
        $apiApp->orgId          = $app->orgId;
        $apiApp->accountManager = $app->accountManager;
        $apiApp->appSettings    = $app->appSettings;
        $apiApp->moduleManager  = $app->moduleManager;
        $apiApp->eventManager   = $app->eventManager;

        $result = [
            'collection' => [],
            'upload'     => [],
            'type'       => [],
        ];

        if (!\is_dir(__DIR__ . '/../../../temp')) {
            \mkdir(__DIR__ . '/../../../temp');
        }

        foreach ($mediaData as $media) {
            switch ($media['type']) {
                case 'collection':
                    $result['collection'][] = self::createCollection($apiApp, $media);
                    break;
                case 'upload':
                    $result['upload'][] = self::uploadMedia($apiApp, $media);
                    break;
                case 'type':
                    $result['type'][] = self::createType($apiApp, $media);
                    break;
                default:
            }
        }

        return $result;
    }

    /**
     * Create collection.
     *
     * @param ApplicationAbstract $app  Application
     * @param array               $data Media info
     *
     * @return Collection
     *
     * @since 1.0.0
     */
    private static function createCollection(ApplicationAbstract $app, array $data) : array
    {
        /** @var \Modules\Media\Controller\ApiController $module */
        $module = $app->moduleManager->getModuleInstance('Media');

        if (!isset($data['path'])) {
            $path = '/Modules/Media/Files' . ($data['virtualPath'] ?? '') . '/' . ($data['name'] ?? '');
        } else {
            $path = $data['path'] ?? '/Modules/Media/Files/' . ($data['name'] ?? '');
        }

        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', $data['name'] ?? '');
        $request->setData('virtualpath', $data['virtualPath'] ?? '/');
        $request->setData('path', $path);
        $request->setData('create_directory', $data['create_directory'] ?? false);

        $module->apiCollectionCreate($request, $response);

        return !\is_array($response->get('')['response'])
            ? $response->get('')['response']->toArray()
            : $response->get('')['response'];
    }

    /**
     * Create type.
     *
     * @param ApplicationAbstract $app  Application
     * @param array               $data Media info
     *
     * @return MediaType
     *
     * @since 1.0.0
     */
    private static function createType(ApplicationAbstract $app, array $data) : array
    {
        /** @var \Modules\Media\Controller\ApiController $module */
        $module = $app->moduleManager->get('Media');

        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', $data['name'] ?? '');

        $module->apiMediaTypeCreate($request, $response);

        $type = $response->get('')['response'];
        $id   = $type->getId();

        foreach ($data['l11n'] as $l11n) {
            $response = new HttpResponse();
            $request  = new HttpRequest(new HttpUri(''));

            $request->header->account = 1;
            $request->setData('title', $l11n['title'] ?? '');
            $request->setData('lang', $l11n['lang'] ?? null);
            $request->setData('type', $id);

            $module->apiMediaTypeL11nCreate($request, $response);
        }

        return !\is_array($type)
            ? $type->toArray()
            : $type;
    }

    /**
     * Upload media.
     *
     * @param ApplicationAbstract $app  Application
     * @param array               $data Media info
     *
     * @return array
     *
     * @since 1.0.0
     */
    private static function uploadMedia(ApplicationAbstract $app, array $data) : array
    {
        /** @var \Modules\Media\Controller\ApiController $module */
        $module = $app->moduleManager->get('Media');

        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('path', empty($data['path'] ?? '') ? '' : $data['path']);
        $request->setData('virtualPath',
            (string) (
                $data['create_collection']
                    ? \rtrim($data['virtualPath'] ?? '/', '/') . '/' . ((string) ($data['name'] ?? ''))
                    : ($data['virtualPath'] ?? '/')
            )
        );
        $request->setData('type', $data['media_type'] ?? null); // = identifier for modules
        $request->setData('pathsettings', $data['path_setting'] ?? PathSettings::FILE_PATH);

        $tempPath = __DIR__ . '/../../../temp/';

        foreach ($data['files'] as $file) {
            $filePath = __DIR__ . '/../../..' . $file;

            if (\is_file($filePath)) {
                File::copy($filePath, $tempPath . $file);

                $request->addFile([
                    'size'     => \filesize($tempPath . $file),
                    'name'     => \basename($file),
                    'tmp_name' => $tempPath . $file,
                    'error'    => \UPLOAD_ERR_OK,
                ]);
            } if (\is_dir($filePath)) {
                Directory::copy($filePath, $tempPath . $file);

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tempPath . $file . '/', \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $item) {
                    if ($item->isDir()) {
                        continue;
                    }

                    $request->addFile([
                        'size'     => \filesize($item->getPathname()),
                        'name'     => \basename($item->getPathname()),
                        'tmp_name' => $item->getPathname(),
                        'error'    => \UPLOAD_ERR_OK,
                    ]);
                }
            }
        }

        $module->apiMediaUpload($request, $response);

        if ($data['create_collection']) {
            $response = new HttpResponse();
            $request  = new HttpRequest(new HttpUri(''));

            $request->header->account = 1;
            $request->setData('name', (string) ($data['name'] ?? ''));
            $request->setData('virtualpath', (string) ($data['virtualPath'] ?? '/'));
            $request->setData('path', (string) ($data['path'] ?? '/Modules/Media/Files/' . ((string) ($data['name'] ?? ''))));

            $module->apiCollectionCreate($request, $response);

            return !\is_array($response->get('')['response']) ? $response->get('')['response']->toArray() : $response->get('')['response'];
        }

        return !\is_array($response->get('')['response'])
            ? $response->get('')['response']->toArray()
            : $response->get('')['response'];
    }
}
