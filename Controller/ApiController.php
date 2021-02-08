<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
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
use phpOMS\Asset\AssetType;
use phpOMS\Message\Http\RequestStatusCode;
use phpOMS\Message\NotificationLevel;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Model\Html\Head;
use phpOMS\Model\Message\FormValidation;
use phpOMS\System\File\FileUtils;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\MimeType;
use phpOMS\Utils\Parser\Markdown\Markdown;
use phpOMS\Views\View;

/**
 * Media class.
 *
 * @package Modules\Media
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class ApiController extends Controller
{
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
            $request->getData('name') === null || \count($request->getFiles()) > 1 ? '' : $request->getData('name'),
            $request->getFiles(),
            $request->header->account,
            __DIR__ . '/../../../Modules/Media/Files' . \urldecode((string) ($request->getData('path') ?? '')),
            \urldecode((string) ($request->getData('virtualpath') ?? '')),
            (string) ($request->getData('type') ?? ''),
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
     * @param string $virtualPath   virtual path The path which is used to visually structure the files, like directories
     * @param string $type          Media type (internal/custom media categorization)
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
        string $type = '',
        string $password = '',
        string $encryptionKey = '',
        int $pathSettings = PathSettings::RANDOM_PATH
    ) : array {
        if (empty($files)) {
            return [];
        }

        $outputDir = '';
        $absolute  = false;

        if ($pathSettings === PathSettings::RANDOM_PATH) {
            $outputDir = self::createMediaPath($basePath);
        } elseif ($pathSettings === PathSettings::FILE_PATH) {
            $outputDir = \rtrim($basePath, '/\\');
            $absolute  = true;
        } else {
            return [];
        }

        $upload = new UploadFile();
        $upload->setOutputDir($outputDir);

        $status = $upload->upload($files, $name, $absolute, $encryptionKey);

        return $this->createDbEntries($status, $account, $virtualPath, $type);
    }

    public static function uploadFilesToDestination(
        array $files,
        string $name = '',
        string $path = '',
    ) : array {
        $upload = new UploadFile();
        $upload->setOutputDir($path);

        $status = $upload->upload($files, $name, true, '');

        return $status;
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
     * @param string $type        Media type (internal categorization)
     * @param string $ip          Ip
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function createDbEntries(
        array $status,
        int $account,
        string $virtualPath = '',
        string $type = '',
        string $ip = '127.0.0.1'
    ) : array {
        $mediaCreated = [];

        foreach ($status as $uFile) {
            if (($created = self::createDbEntry($uFile, $account, $virtualPath, $type)) !== null) {
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
     * @param string $type        Media type (internal categorization)
     *
     * @return null|Media
     *
     * @since 1.0.0
     */
    public static function createDbEntry(array $status, int $account, string $virtualPath = '', string $type = '') : ?Media
    {
        if ($status['status'] !== UploadStatus::OK) {
            return null;
        }

        $media = new Media();

        $media->setPath(self::normalizeDbPath($status['path']) . '/' . $status['filename']);
        $media->name      = $status['name'];
        $media->size      = $status['size'];
        $media->createdBy = new NullAccount($account);
        $media->extension = $status['extension'];
        $media->setVirtualPath($virtualPath);
        $media->type = $type;

        MediaMapper::create($media);

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
            throw new \Exception(); // @codeCoverageIgnore
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

        $this->updateModel($request->header->account, $old, $new, MediaMapper::class, 'media', $request->getOrigin());
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
        $media              = MediaMapper::get($id);
        $media->name        = (string) ($request->getData('name') ?? $media->name);
        $media->description = (string) ($request->getData('description') ?? $media->description);
        $media->setPath((string) ($request->getData('path') ?? $media->getPath()));
        $media->setVirtualPath(\urldecode((string) ($request->getData('virtualpath') ?? $media->getVirtualPath())));

        // @todo: implement a security check to ensure the user is allowed to write to the file. Right now you could overwrite ANY file with a malicious $path
        if ($id === 0
            && $media instanceof NullMedia
            && \is_file(__DIR__ . '/../Files' . ($path = \urldecode($request->getData('path'))))
        ) {
            $name = \explode('.', \basename($path));

            $media->name      = $name[0];
            $media->extension = $name[1] ?? '';
            $media->setVirtualPath(\dirname($path));
            $media->setPath('/Modules/Media/Files/' . \ltrim($path, '\\/'));
            $media->isAbsolute = false;
        }

        if ($request->getData('content') !== null) {
            \file_put_contents(
                $media->isAbsolute ? $media->getPath() : __DIR__ . '/../../../' . \ltrim($media->getPath(), '\\/'),
                $request->getData('content')
            );

            $media->size = \strlen($request->getData('content'));
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
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $collection = $this->createCollectionFromRequest($request);
        $this->createModel($request->header->account, $collection, CollectionMapper::class, 'collection', $request->getOrigin());
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
        $mediaCollection                 = new Collection();
        $mediaCollection->name           = $request->getData('name') ?? '';
        $mediaCollection->description    = ($description = Markdown::parse($request->getData('description') ?? ''));
        $mediaCollection->descriptionRaw = $description;
        $mediaCollection->createdBy      = new NullAccount($request->header->account);

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
     * This doesn't create a database entry only the collection model.
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
        if (empty($media)
            || !$this->app->accountManager->get($account)->hasPermission(
                PermissionType::CREATE, $this->app->orgId, null, self::MODULE_NAME, PermissionState::COLLECTION, null)
        ) {
            return new NullCollection();
        }

        /* Create collection */
        $mediaCollection                 = new Collection();
        $mediaCollection->name           = $name;
        $mediaCollection->description    = Markdown::parse($description);
        $mediaCollection->descriptionRaw = $description;
        $mediaCollection->createdBy      = new NullAccount($account);
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
        $fileName    = (string) ($request->getData('filename') ?? ($request->getData('name') ?? ''));
        $fileName   .= \strripos($fileName, '.') === false ? '.txt' : '';

        $outputDir = '';
        if (empty($request->getData('path'))) {
            $outputDir = self::createMediaPath(__DIR__ . '/../../../Modules/Media/Files');
        } else {
            if (\stripos(
                    FileUtils::absolute(__DIR__ . '/../../../Modules/Media/Files/' . \ltrim($path, '\\/')),
                    FileUtils::absolute(__DIR__ . '/../../../')
                ) !== 0
            ) {
                $outputDir = self::createMediaPath(__DIR__ . '/../../../Modules/Media/Files');
            } else {
                $outputDir = __DIR__ . '/../../../Modules/Media/Files/' . \ltrim($path, '\\/');
            }
        }

        if (!\is_dir($outputDir)) {
            $created = Directory::create($outputDir, 0775, true);

            if (!$created) {
                throw new \Exception('Couldn\'t create outputdir: "' . $outputDir . '"'); // @codeCoverageIgnore
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

        $created = $this->createDbEntries($status, $request->header->account, $virtualPath, $request->getData('type') ?? '');

        $ids = [];
        foreach ($created as $file) {
            $ids[] = $file->getId();
        }

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Media', 'Media successfully created.', $ids);
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param HttpRequest  $request  Request
     * @param HttpResponse $response Response
     * @param mixed        $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaExport(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        /** @var Media $media */
        $media  = MediaMapper::get((int) $request->getData('id'));

        $view = $this->createView($media, $request, $response);
        $this->setMediaResponseHeader($view, $media, $request, $response);
        $view->setData('path', __DIR__ . '/../../../');

        $response->set('export', $view);
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param Media        $media    Media
     * @param HttpRequest  $request  Request
     * @param HttpResponse $response Response
     *
     * @return View
     *
     * @since 1.0.0
     */
    public function createView(Media $media, RequestAbstract $request, ResponseAbstract $response) : View
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setData('media', $media);

        if (($type = $request->getData('type')) === null) {
            $view->setTemplate('/Modules/Media/Theme/Api/render');
        } elseif ($type === 'html') {
            $head = new Head();
            $css  = \file_get_contents(__DIR__ . '/../../../Web/Backend/css/backend-small.css');
            if ($css === false) {
                $css = '';
            }

            $css = \preg_replace('!\s+!', ' ', $css);
            $head->setStyle('core', $css ?? '');

            $head->addAsset(AssetType::CSS, 'cssOMS/styles.css');
            $view->setData('head', $head);

            switch (\strtolower($media->extension)) {
                case 'xls':
                case 'xlsx':
                    $view->setTemplate('/Modules/Media/Theme/Api/spreadsheetAsHtml');
                    break;
                case 'doc':
                case 'docx':
                    $view->setTemplate('/Modules/Media/Theme/Api/wordAsHtml');
                    break;
            }
        }

        return $view;
    }

    /**
     * Set header for report/template
     *
     * @param View             $view     Media view
     * @param string           $name     Template name
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function setMediaResponseHeader(View $view, Media $media, RequestAbstract $request, ResponseAbstract $response) : void
    {
        switch ($request->getData('type') ?? \strtolower($media->extension)) {
            case 'htm':
            case 'html':
                $response->header->set('Content-Type', MimeType::M_HTML, true);
                break;
            case 'pdf':
                $response->header->set('Content-Type', MimeType::M_PDF, true);
                break;
            case 'c':
            case 'cpp':
            case 'h':
            case 'php':
            case 'js':
            case 'css':
            case 'rs':
            case 'py':
            case 'r':
                $response->header->set('Content-Type', MimeType::M_TXT, true);
                break;
            case 'txt':
            case 'cfg':
            case 'log':
                $response->header->set('Content-Type', MimeType::M_TXT, true);
                break;
            case 'md':
                $response->header->set('Content-Type', MimeType::M_TXT, true);
                break;
            case 'csv':
                $response->header->set('Content-Type', MimeType::M_CSV, true);
                break;
            case 'xls':
                $response->header->set('Content-Type', MimeType::M_XLS, true);
                break;
            case 'xlsx':
                $response->header->set('Content-Type', MimeType::M_XLSX, true);
                break;
            case 'doc':
                $response->header->set('Content-Type', MimeType::M_DOC, true);
                break;
            case 'docx':
                $response->header->set('Content-Type', MimeType::M_DOCX, true);
                break;
            case 'ppt':
                $response->header->set('Content-Type', MimeType::M_PPT, true);
                break;
            case 'pptx':
                $response->header->set('Content-Type', MimeType::M_PPTX, true);
                break;
            case 'json':
                $response->header->set('Content-Type', MimeType::M_CSV, true);
                break;
            case 'jpg':
            case 'jpeg':
                $response->header->set('Content-Type', MimeType::M_JPG, true);
                break;
            case 'gif':
                $response->header->set('Content-Type', MimeType::M_GIF, true);
                break;
            case 'png':
                $response->header->set('Content-Type', MimeType::M_PNG, true);
                break;
            case 'mp3':
                $response->header->set('Content-Type', MimeType::M_MP3, true);
                break;
            case 'mp4':
                $response->header->set('Content-Type', MimeType::M_MP4, true);
                break;
            case 'mpeg':
                $response->header->set('Content-Type', MimeType::M_MPEG, true);
                break;
            default:
                $response->header->set('Content-Type', MimeType::M_BIN, true);
        }
    }
}
