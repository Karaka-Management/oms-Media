<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Controller;

use Modules\Admin\Models\AccountPermission;
use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaContent;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\MediaType;
use Modules\Media\Models\MediaTypeL11n;
use Modules\Media\Models\MediaTypeL11nMapper;
use Modules\Media\Models\MediaTypeMapper;
use Modules\Media\Models\NullCollection;
use Modules\Media\Models\NullMedia;
use Modules\Media\Models\NullMediaType;
use Modules\Media\Models\PathSettings;
use Modules\Media\Models\PermissionCategory;
use Modules\Media\Models\UploadFile;
use Modules\Media\Models\UploadStatus;
use Modules\Tag\Models\NullTag;
use phpOMS\Account\PermissionType;
use phpOMS\Asset\AssetType;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\HttpResponse;
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
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;
use phpOMS\Application\ApplicationAbstract;
use phpOMS\Autoloader;
use phpOMS\Utils\Parser\Pdf\PdfParser;

/**
 * Media class.
 *
 * @package Modules\Media
 * @license OMS License 1.0
 * @link    https://karaka.app
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
            names:         $request->getDataList('names') ?? [],
            fileNames:     $request->getDataList('filenames') ?? [],
            files:         $request->getFiles(),
            account:       $request->header->account,
            basePath:      __DIR__ . '/../../../Modules/Media/Files' . \urldecode((string) ($request->getData('path') ?? '')),
            virtualPath:   \urldecode((string) ($request->getData('virtualpath') ?? '')),
            type:          $request->getData('type', 'int'),
            password:      (string) ($request->getData('password') ?? ''),
            encryptionKey: (string) ($request->getData('encrypt') ?? ''),
            pathSettings:  (int) ($request->getData('pathsettings') ?? PathSettings::RANDOM_PATH) // IMPORTANT!!!
        );

        $ids = [];
        foreach ($uploads as $file) {
            $ids[] = $file->getId();
            // add tags
            if (!empty($tags = $request->getDataJson('tags'))) {
                foreach ($tags as $tag) {
                    if (!isset($tag['id'])) {
                        $request->setData('title', $tag['title'], true);
                        $request->setData('color', $tag['color'], true);
                        $request->setData('icon', $tag['icon'] ?? null, true);
                        $request->setData('language', $tag['language'], true);

                        $internalResponse = new HttpResponse();
                        $this->app->moduleManager->get('Tag')->apiTagCreate($request, $internalResponse, null);
                        $file->addTag($tId = $internalResponse->get($request->uri->__toString())['response']);
                    } else {
                        $file->addTag(new NullTag($tId = (int) $tag['id']));
                    }

                    $this->createModelRelation(
                        $request->header->account,
                        $file->getId(),
                        $tId,
                        MediaMapper::class,
                        'tags',
                        '',
                        $request->getOrigin()
                    );
                }
            }
        }

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Media', 'Media successfully created.', $ids);
    }

    /**
     * Upload a media file
     *
     * @param array  $names         Database names
     * @param array  $fileNames     FileNames
     * @param array  $files         Files
     * @param int    $account       Uploader
     * @param string $basePath      Base path. The path which is used for the upload.
     * @param string $virtualPath   virtual path The path which is used to visually structure the files, like directories
     * @param int    $type          Media type (internal/custom media categorization)
     *                              The file storage on the system can be different
     * @param string $password      File password. The password to protect the file (only database)
     * @param string $encryptionKey Encryption key. Used to encrypt the file on the local file storage.
     * @param int    $pathSettings  Settings which describe where the file should be uploaded to (physically)
     *                              RANDOM_PATH = random location in the base path
     *                              FILE_PATH   = combination of base path and virtual path
     * @param bool   $hasAccountRelation The uploaded files should be related to an account
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function uploadFiles(
        array $names,
        array $fileNames,
        array $files,
        int $account,
        string $basePath = '/Modules/Media/Files',
        string $virtualPath = '',
        int $type = null,
        string $password = '',
        string $encryptionKey = '',
        int $pathSettings = PathSettings::RANDOM_PATH,
        bool $hasAccountRelation = true
    ) : array
    {
        if (empty($files)) {
            return [];
        }

        $outputDir = '';
        $absolute  = false;

        // @todo sandatize $basePath, we don't know if it might be relative!

        if ($pathSettings === PathSettings::RANDOM_PATH) {
            $outputDir = self::createMediaPath($basePath);
        } elseif ($pathSettings === PathSettings::FILE_PATH) {
            $outputDir = \rtrim($basePath, '/\\');
            $absolute  = true;
        } else {
            return [];
        }

        $upload            = new UploadFile();
        $upload->outputDir = $outputDir;
        $upload->preserveFileName = empty($fileNames) || \count($fileNames) === \count($files);

        $status = $upload->upload($files, $fileNames, $absolute, $encryptionKey);

        $sameLength = \count($names) === \count($status);
        $nCounter   = -1;

        $created = [];
        foreach ($status as &$stat) {
            ++$nCounter;

            // Possible: name != filename (name = database media name, filename = name on the file system)
            $stat['name'] = $sameLength ? $names[$nCounter] : $stat['filename'];

            $created[] = self::createDbEntry(
                $stat,
                $account,
                $virtualPath,
                $type,
                app: $hasAccountRelation ? $this->app : null
            );
        }

        return $created;
    }

    /**
     * Uploads a file to a destination
     *
     * @param array  $files     Files to upload
     * @param array  $fileNames Names on the directory
     * @param string $path      Upload path
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function uploadFilesToDestination(
        array $files,
        array $fileNames = [],
        string $path = '',
    ) : array
    {
        $upload            = new UploadFile();
        $upload->outputDir = $path;

        return $upload->upload($files, $fileNames, true, '');
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
    public static function createMediaPath(string $basePath = '/Modules/Media/Files') : string
    {
        $rndPath = \str_pad(\dechex(\mt_rand(0, 4294967295)), 8, '0', \STR_PAD_LEFT);
        return $basePath . '/_' . $rndPath[0] . $rndPath[1] . $rndPath[2] . $rndPath[3] . '/_' . $rndPath[4] . $rndPath[5] . $rndPath[6] . $rndPath[7];
    }

    /**
     * Create db entry for uploaded file
     *
     * @param array    $status      Files
     * @param int      $account     Uploader
     * @param string   $virtualPath Virtual path (not on the hard-drive)
     * @param null|int $type        Media type (internal categorization)
     * @param ApplicationAbstract     $app Should create relation to uploader
     *
     * @return null|Media
     *
     * @since 1.0.0
     */
    public static function createDbEntry(
        array $status,
        int $account,
        string $virtualPath = '',
        int $type = null,
        string $ip = '127.0.0.1',
        ApplicationAbstract $app = null
    ) : ?Media
    {
        if ($status['status'] !== UploadStatus::OK) {
            return null;
        }

        $media = new Media();

        $media->setPath(self::normalizeDbPath($status['path']) . '/' . $status['filename']);
        $media->name      = empty($status['name']) ? $status['filename'] : $status['name'];
        $media->size      = $status['size'];
        $media->createdBy = new NullAccount($account);
        $media->extension = $status['extension'];
        $media->setVirtualPath($virtualPath);
        $media->type = $type === null ? null : new NullMediaType($type);

        if (\is_file($media->getAbsolutePath())) {
            $content = self::loadFileContent($media->getAbsolutePath(), $media->extension);

            if (!empty($content)) {
                $media->content = new MediaContent();
                $media->content->content = $content;
            }
        }

        MediaMapper::create()->execute($media);

        if ($app !== null) {
            $app->moduleManager->get('Admin')->createAccountModelPermission(
                new AccountPermission(
                    $account,
                    $app->orgId,
                    $app->appName,
                    self::NAME,
                    self::NAME,
                    PermissionCategory::MEDIA,
                    $media->getId(),
                    null,
                    PermissionType::READ | PermissionType::MODIFY | PermissionType::DELETE | PermissionType::PERMISSION
                ),
                $account,
                $ip
            );
        }

        return $media;
    }

    private static function loadFileContent(string $path, string $extension) : string
    {
        switch ($extension) {
            case 'pdf':
                return PdfParser::pdf2text($path);
                break;
            case 'doc':
            case 'docx':
                Autoloader::addPath(__DIR__ . '/../../../Resources/');

                $reader = IOFactory::createReader('Word2007');
                $doc    = $reader->load($path);

                $writer = new HTML($doc);
                return $writer->getContent();
                break;
            case 'txt':
            case 'md':
                return \file_get_contents($path);
                break;
            default:
                return '';
        };
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
    public static function normalizeDbPath(string $path) : string
    {
        $realpath = \realpath(__DIR__ . '/../../../');
        if ($realpath === false) {
            throw new \Exception(); // @codeCoverageIgnore
        }

        return FileUtils::absolute(\str_replace('\\', '/',
            \str_replace($realpath, '',
                \rtrim($path, '\\/')
            )
        ));
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
        $old = clone MediaMapper::get()->where('id', (int) $request->getData('id'))->execute();

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
        $media              = MediaMapper::get()->where('id', $id)->execute();
        $media->name        = (string) ($request->getData('name') ?? $media->name);
        $media->description = (string) ($request->getData('description') ?? $media->description);
        $media->setPath((string) ($request->getData('path') ?? $media->getPath()));
        $media->setVirtualPath(\urldecode((string) ($request->getData('virtualpath') ?? $media->getVirtualPath())));

        // @todo: implement a security check to ensure the user is allowed to write to the file. Right now you could overwrite ANY file with a malicious $path
        if ($id === 0
            && $media instanceof NullMedia
            && \is_file($fullPath = __DIR__ . '/../Files' . ($path = \urldecode($request->getData('path'))))
            && \stripos(FileUtils::absolute(__DIR__ . '/../Files/'), FileUtils::absolute($fullPath)) === 0
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

        $dirPath   = $outputDir . '/' . $request->getData('name');
        $outputDir = \substr($outputDir, \strlen(__DIR__ . '/../../..'));

        $mediaCollection->setVirtualPath($virtualPath);
        $mediaCollection->setPath($outputDir);

        CollectionMapper::create()->execute($mediaCollection);

        if (((bool) ($request->getData('create_directory') ?? false))
            && !\is_dir($dirPath)) {
            \mkdir($dirPath, 0755, true);
        }

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
                PermissionType::CREATE, $this->app->orgId, null, self::NAME, PermissionCategory::COLLECTION, null)
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

    public function createRecursiveMediaCollection(string $basePath, string $path, int $account, string $physicalPath = '') : Collection
    {
        $status = false;
        if (!empty($physicalPath)) {
            $status = !\is_dir($physicalPath) ? \mkdir($physicalPath, 0755, true) : true;
        }

        $path = \trim($path, '/');
        $paths = \explode('/', $path);
        $tempPaths = $paths;
        $length = \count($paths);

        $temp = '';

        /** @var Collection $parentCollection */
        $parentCollection = null;

        for ($i = $length; $i > 0; --$i) {
            $temp = '/' . \implode('/', $tempPaths);

            /** @var Collection $parentCollection */
            $parentCollection = CollectionMapper::getParentCollection($temp)->execute();
            if ($parentCollection->getId() > 0) {
                break;
            }

            \array_pop($tempPaths);
        }

        for (; $i < $length; ++$i) {
            /* Create collection */
            $childCollection                 = new Collection();
            $childCollection->name           = $paths[$i];
            $childCollection->createdBy      = new NullAccount($account);
            $childCollection->setVirtualPath('/'. \ltrim($temp, '/'));
            $childCollection->setPath('/Modules/Media/Files' . $temp);

            CollectionMapper::create()->execute($childCollection);
            CollectionMapper::writer()->createRelationTable('sources', [$childCollection->getId()], $parentCollection->getId());

            $parentCollection = $childCollection;
            $temp .= '/' . $paths[$i];
        }

        return $parentCollection;
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
        $virtualPath = \urldecode((string) ($request->getData('virtualpath') ?? '/'));
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

        $ids = [];
        foreach ($status as $stat) {
            $created = self::createDbEntry(
                $status,
                $request->header->account,
                $virtualPath,
                $request->getData('type', 'int'),
                $request->getOrigin(),
                $this->app
            );

            $ids[] = $created->getId();
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
        if (((int) $request->getData('id')) !== 0) {
            /** @var Media $media */
            $media = MediaMapper::get()->where('id', (int) $request->getData('id'))->execute();
        } else {
            $path  = \urldecode($request->getData('path'));
            $media = new NullMedia();
            if (\is_file(__DIR__ . '/../../../' . \ltrim($path, '\\/'))) {
                $name = \explode('.', \basename($path));

                $media->name      = $name[0];
                $media->extension = $name[1] ?? '';
                $media->setVirtualPath(\dirname($path));
                $media->setPath('/' . \ltrim($path, '\\/'));
                $media->isAbsolute = false;
            }
        }

        $this->setMediaResponseHeader($media, $request, $response);
        $view = $this->createView($media, $request, $response);
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

        if (!\headers_sent()) {
            $response->endAllOutputBuffering(); // for large files
        }

        if (($type = $request->getData('type')) === null) {
            $view->setTemplate('/Modules/Media/Theme/Api/render');
        } elseif ($type === 'html') {
            $head = new Head();
            $css  = \file_get_contents(__DIR__ . '/../../../Web/Backend/css/backend-small.css');
            if ($css === false) {
                $css = ''; // @codeCoverageIgnore
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
     * @param Media            $media    Media file
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     *
     * @return void
     *
     * @since 1.0.0
     */
    private function setMediaResponseHeader(Media $media, RequestAbstract $request, ResponseAbstract $response) : void
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
            case 'md':
                $response->header->set('Content-Type', MimeType::M_TXT, true);
                break;
            case 'csv':
            case 'json':
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

    /**
     * Validate document create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateMediaTypeCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['name'] = empty($request->getData('name')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create document
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
    public function apiMediaTypeCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateMediaTypeCreate($request))) {
            $response->set('media_type_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $type = $this->createDocTypeFromRequest($request);
        $this->createModel($request->header->account, $type, MediaTypeMapper::class, 'doc_type', $request->getOrigin());

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Media', 'Media type successfully created', $type);
    }

     /**
     * Method to create task from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return EditorDoc
     *
     * @since 1.0.0
     */
    private function createDocTypeFromRequest(RequestAbstract $request) : MediaType
    {
        $type       = new MediaType();
        $type->name = $request->getData('name');

        if (!empty($request->getData('title'))) {
            $type->setL11n($request->getData('title'), $request->getData('lang') ?? $request->getLanguage());
        }

        return $type;
    }

    /**
     * Validate l11n create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool>
     *
     * @since 1.0.0
     */
    private function validateMediaTypeL11nCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['title'] = empty($request->getData('title')))
            || ($val['type'] = empty($request->getData('type')))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create tag localization
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
    public function apiMediaTypeL11nCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        if (!empty($val = $this->validateMediaTypeL11nCreate($request))) {
            $response->set('media_type_l11n_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $l11nMediaType = $this->createMediaTypeL11nFromRequest($request);
        $this->createModel($request->header->account, $l11nMediaType, MediaTypeL11nMapper::class, 'media_type_l11n', $request->getOrigin());

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Localization', 'Category localization successfully created', $l11nMediaType);
    }

    /**
     * Method to create tag localization from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return MediaTypeL11n
     *
     * @since 1.0.0
     */
    private function createMediaTypeL11nFromRequest(RequestAbstract $request) : MediaTypeL11n
    {
        $l11nMediaType           = new MediaTypeL11n();
        $l11nMediaType->type = (int) ($request->getData('type') ?? 0);
        $l11nMediaType->setLanguage((string) (
            $request->getData('language') ?? $request->getLanguage()
        ));
        $l11nMediaType->title = (string) ($request->getData('title') ?? '');

        return $l11nMediaType;
    }
}
