<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\Controller;

use Modules\Admin\Models\AccountPermission;
use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\NullCollection;
use Modules\Media\Models\NullMedia;
use Modules\Media\Models\PathSettings;
use Modules\Media\Models\PermissionState;
use Modules\Media\Models\UploadFile;
use Modules\Media\Models\UploadStatus;
use phpOMS\Account\PermissionType;
use phpOMS\Message\NotificationLevel;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Model\Message\FormValidation;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\MimeType;
use phpOMS\Utils\Parser\Markdown\Markdown;
use phpOMS\Message\Http\RequestStatusCode;

/**
 * Media class.
 *
 * @package Modules\Media
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 *
 * @todo Orange-Management/Modules#50
 *  Allow collection modification
 *  Allow to change (add/remove) collection components.
 *
 * @todo Orange-Management/Modules#59
 *  Implement resumable uploads
 *  This is especially useful if someone wants to upload when he/she has wifi access
 *
 * @todo Orange-Management/Modules#150
 *  Allow to create new files (not only upload)
 *  In many cases it would be nice to create a new file manually with the module (e.g. create a new .txt or .sqlite file) which then can get edited directly in the media module.
 *
 * @todo Orange-Management/Modules#160
 *  Fix media upload
 *
 * @todo Orange-Management/oms-Media#3
 *  [MultiUpload] Allow to create a collection when uploading multiple files
 *
 * @todo Orange-Management/oms-Media#14
 *  [Download] Allow to download files
 *
 * @todo Orange-Management/oms-Media#15
 *  [Download] Allow to download collections
 *  Downloading a collection should return a zip or tar.gz
 */
final class ApiController extends Controller
{
    /**
     * Api method to find media
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaFind(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $response->getHeader()->set('Content-Type', MimeType::M_JSON, true);
        $response->set(
            $request->getUri()->__toString(),
            \array_values(
                MediaMapper::find((string) ($request->getData('search') ?? ''))
            )
        );
    }

    /**
     * Api method to upload media file.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaUpload(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $uploads = $this->uploadFiles(
            $request->getData('name') === null || $request->getFiles() !== null ? '' : $request->getData('name'),
            $request->getFiles(),
            $request->getHeader()->getAccount(),
            __DIR__ . '/../../../Modules/Media/Files' . ((string) ($request->getData('path') ?? '')),
            (string) ($request->getData('virtualPath') ?? ''),
            (string) ($request->getData('password') ?? ''),
            (string) ($request->getData('encrypt') ?? ''),
            (int) ($request->getData('pathsettings') ?? PathSettings::RANDOM_PATH)
        );

        $ids = [];
        foreach ($uploads as $file) {
            $ids[] = $file->getId();
        }

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Media', 'Media successfully created.', $ids);
    }

    /**
     * Upload a media file
     *
     * @param string $name          Name
     * @param array  $files         Files
     * @param int    $account       Uploader
     * @param string $basePath      Base path. The path which is used for the upload.
     * @param string $virtualPath   Virtual path The path which is used to visually structure the files, like directories.
     *                              The file storage on the system can be different
     * @param string $password      File password. The password to protect the file (only database)
     * @param string $encryptionKey Encryption key. Used to encrypt the file on the local file storage.
     * @param int    $pathSettings  Settings which describe where the file should be uploaded to (physically)
     *                              RANDOM_PATH = random location in the base path
     *                              FILE_PATH   = combination of base path and virtual path
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function uploadFiles(
        string $name,
        array $files,
        int $account,
        string $basePath = '/Modules/Media/Files',
        string $virtualPath = '',
        string $password = '',
        string $encryptionKey = '',
        int $pathSettings = PathSettings::RANDOM_PATH
    ) : array {
        $mediaCreated = [];

        if (!empty($files)) {
            $outputDir = '';
            $absolute  = false;

            if ($pathSettings === PathSettings::RANDOM_PATH) {
                $outputDir = self::createMediaPath($basePath);
            } elseif ($pathSettings === PathSettings::FILE_PATH) {
                $outputDir = \rtrim($basePath, '/\\') . $virtualPath;
                $absolute  = true;
            } else {
                return $mediaCreated;
            }

            $upload = new UploadFile();
            $upload->setOutputDir($outputDir);

            $status       = $upload->upload($files, $name, $absolute, $encryptionKey);
            $mediaCreated = $this->createDbEntries($status, $account, $virtualPath);
        }

        return $mediaCreated;
    }

    /**
     * Create a random file path to store media files
     *
     * @param string $basePath Base path for file storage
     *
     * @return string Random path to store media files
     *
     * @since 1.0.0
     */
    public static function createMediaPath(string $basePath = 'Modules/Media/Files') : string
    {
        $rndPath = \str_pad(\dechex(\mt_rand(0, 65535)), 4, '0', \STR_PAD_LEFT);
        return $basePath . '/' . $rndPath[0] . $rndPath[1] . '/' . $rndPath[2] . $rndPath[3];
    }

    /**
     * @param array  $status      Files
     * @param int    $account     Uploader
     * @param string $virtualPath Virtual path
     * @param string $ip          Ip
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function createDbEntries(array $status, int $account, string $virtualPath = '', string $ip = '127.0.0.1') : array
    {
        $mediaCreated = [];

        foreach ($status as $uFile) {
            if (($created = self::createDbEntry($uFile, $account, $virtualPath)) !== null) {
                $mediaCreated[] = $created;

                $this->app->moduleManager->get('Admin')->createAccountModelPermission(
                    new AccountPermission(
                        $account,
                        $this->app->orgId,
                        $this->app->appName,
                        self::MODULE_NAME,
                        PermissionState::MEDIA,
                        $created->getId(),
                        null,
                        PermissionType::READ | PermissionType::MODIFY | PermissionType::DELETE | PermissionType::PERMISSION
                    ),
                    $account,
                    $ip
                );
            }
        }

        return $mediaCreated;
    }

    /**
     * Create db entry for uploaded file
     *
     * @param array  $status      Files
     * @param int    $account     Uploader
     * @param string $virtualPath Virtual path (not on the hard-drive)
     *
     * @return null|Media
     *
     * @since 1.0.0
     */
    public static function createDbEntry(array $status, int $account, string $virtualPath = '') : ?Media
    {
        $media = null;

        if ($status['status'] === UploadStatus::OK) {
            $media = new Media();

            $media->setPath(self::normalizeDbPath($status['path']) . '/' . $status['filename']);
            $media->setName($status['name']);
            $media->setSize($status['size']);
            $media->setCreatedBy(new NullAccount($account));
            $media->setExtension($status['extension']);
            $media->setVirtualPath($virtualPath);

            MediaMapper::create($media);
        }

        return $media;
    }

    /**
     * Normalize the file path
     *
     * @param string $path Path to the file
     *
     * @return string
     *
     * @since 1.0.0
     */
    private static function normalizeDbPath(string $path) : string
    {
        $realpath = \realpath(__DIR__ . '/../../../');

        if ($realpath === false) {
            throw new \Exception();
        }

        return \str_replace('\\', '/',
            \str_replace($realpath, '',
                \rtrim($path, '\\/')
            )
        );
    }

    /**
     * Api method to update media.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaUpdate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        /** @var Media $old */
        $old = clone MediaMapper::get((int) $request->getData('id'));

        /** @var Media $new */
        $new = $this->updateMediaFromRequest($request);

        $this->updateModel($request->getHeader()->getAccount(), $old, $new, MediaMapper::class, 'media', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Media', 'Media successfully updated', $new);
    }

    /**
     * Method to update media from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return Media
     *
     * @since 1.0.0
     */
    private function updateMediaFromRequest(RequestAbstract $request) : Media
    {
        $id = (int) $request->getData('id');

        /** @var Media $media */
        $media = MediaMapper::get($id);
        $media->setName((string) ($request->getData('name') ?? $media->getName()));
        $media->setVirtualPath(\urldecode((string) ($request->getData('virtualpath') ?? $media->getVirtualPath())));

        if ($id == 0) {
            $path = \urldecode($request->getData('path'));

            if ($media instanceof NullMedia
                && \is_file(__DIR__ . '/../Files' . $path)
            ) {
                $name = \explode('.', \basename($path));

                $media->setName($name[0]);
                $media->setExtension($name[1] ?? '');
                $media->setVirtualPath(\dirname($path));
                $media->setPath('/Modules/Media/Files/' . \ltrim($path, '\\/'));
                $media->setAbsolute(false);
            }
        }

        if ($request->getData('content') !== null) {
            \file_put_contents(
                $media->isAbsolute() ? $media->getPath() : __DIR__ . '/../../../' . \ltrim($media->getPath(), '\\/'),
                $request->getData('content')
            );

            $media->setSize(\strlen($request->getData('content')));
        }

        return $media;
    }

    /**
     * Api method to create a collection.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiCollectionCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateCollectionCreate($request))) {
            $response->set('collection_create', new FormValidation($val));
            $response->getHeader()->setStatusCode(RequestStatusCode::R_400);

            return;
        }

        $collection = $this->createCollectionFromRequest($request);
        $this->createModel($request->getHeader()->getAccount(), $collection, CollectionMapper::class, 'collection', $request->getOrigin());
        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Collection', 'Collection successfully created.', $collection);
    }

    /**
     * Validate collection create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool> Returns the validation array of the request
     *
     * @since 1.0.0
     */
    private function validateCollectionCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['name'] = empty($request->getData('name')))) {
            return $val;
        }

        return [];
    }

    /**
     * Method to create collection from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return Collection Returns the collection from the request
     *
     * @since 1.0.0
     */
    private function createCollectionFromRequest(RequestAbstract $request) : Collection
    {
        $mediaCollection = new Collection();
        $mediaCollection->setName($request->getData('name') ?? '');
        $mediaCollection->setDescription($description = Markdown::parse($request->getData('description') ?? ''));
        $mediaCollection->setDescriptionRaw($description);
        $mediaCollection->setCreatedBy(new NullAccount($request->getHeader()->getAccount()));

        $media = $request->getDataJson('media-list');
        foreach ($media as $file) {
            $mediaCollection->addSource(new NullMedia((int) $file));
        }

        $virtualPath = \urldecode((string) ($request->getData('virtualpath') ?? '/'));

        $outputDir = '';
        if (empty($request->getData('path'))) {
            $outputDir = self::createMediaPath(__DIR__ . '/../../../Modules/Media/Files');
        } else {
            $outputDir = __DIR__ . '/../../../Modules/Media/Files/' . \ltrim($request->getData('path'), '\\/');
            Directory::create($outputDir . '/' . $request->getData('name'), 0775, true);
        }

        $outputDir = \substr($outputDir, \strlen(__DIR__ . '/../../..'));

        $mediaCollection->setVirtualPath($virtualPath);
        $mediaCollection->setPath($outputDir);

        CollectionMapper::create($mediaCollection);

        return $mediaCollection;
    }

    /**
     * Method to create media collection from request.
     *
     * @param string  $name        Collection name
     * @param string  $description Description
     * @param Media[] $media       Media files to create the collection from
     * @param int     $account     Account Id
     *
     * @return Collection
     *
     * @since 1.0.0
     */
    public function createMediaCollectionFromMedia(string $name, string $description, array $media, int $account) : Collection
    {
        if (empty($media)) {
            return new NullCollection();
        }

        // is allowed to create media file
        if (!$this->app->accountManager->get($account)->hasPermission(
            PermissionType::CREATE, $this->app->orgId, null, self::MODULE_NAME, PermissionState::COLLECTION, null)
        ) {
            return new NullCollection();
        }

        /* Create collection */
        $mediaCollection = new Collection();
        $mediaCollection->setName($name);
        $mediaCollection->setDescription(Markdown::parse($description));
        $mediaCollection->setDescriptionRaw($description);
        $mediaCollection->setCreatedBy(new NullAccount($account));
        $mediaCollection->setSources($media);
        $mediaCollection->setVirtualPath('/');
        $mediaCollection->setPath('/Modules/Media/Files');

        return $mediaCollection;
    }

    /**
     * Api method to create media file.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        $path        = \urldecode((string) ($request->getData('path') ?? ''));
        $virtualPath = \urldecode((string) ($request->getData('virtualpath') ?? ''));
        $fileName    = (string) ($request->getData('fileName') ?? ($request->getData('name') ?? ''));
        $fileName   .= \strripos($fileName, '.') === false ? '.txt' : '';

        $outputDir = '';
        if (empty($request->getData('path'))) {
            $outputDir = self::createMediaPath(__DIR__ . '/../../../Modules/Media/Files');
        } else {
            $outputDir = __DIR__ . '/../../../Modules/Media/Files/' . \ltrim($path, '\\/');
        }

        if (!\is_dir($outputDir)) {
            $created = Directory::create($outputDir, 0775, true);

            if (!$created) {
                throw new \Exception('Couldn\'t create outputdir: "' . $outputDir . '"');
            }
        }

        \file_put_contents($outputDir . '/' . $fileName, (string) ($request->getData('content') ?? ''));
        $outputDir = \substr($outputDir, \strlen(__DIR__ . '/../../..'));

        $status = [
            [
                'status'    => UploadStatus::OK,
                'path'      => $outputDir,
                'filename'  => $fileName,
                'name'      => $request->getData('name') ?? '',
                'size'      => \strlen((string) ($request->getData('content') ?? '')),
                'extension' => \substr($fileName, \strripos($fileName, '.') + 1),
            ],
        ];

        $created = $this->createDbEntries($status, $request->getHeader()->getAccount(), $virtualPath);

        $ids = [];
        foreach ($created as $file) {
            $ids[] = $file->getId();
        }

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Media', 'Media successfully created.', $ids);
    }
}
