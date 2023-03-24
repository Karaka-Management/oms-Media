<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Controller;

use Modules\Admin\Models\AccountPermission;
use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaContent;
use Modules\Media\Models\MediaContentMapper;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\MediaType;
use Modules\Media\Models\MediaTypeL11nMapper;
use Modules\Media\Models\MediaTypeMapper;
use Modules\Media\Models\NullCollection;
use Modules\Media\Models\NullMedia;
use Modules\Media\Models\NullMediaType;
use Modules\Media\Models\PathSettings;
use Modules\Media\Models\PermissionCategory;
use Modules\Media\Models\Reference;
use Modules\Media\Models\ReferenceMapper;
use Modules\Media\Models\UploadFile;
use Modules\Media\Models\UploadStatus;
use Modules\Tag\Models\NullTag;
use phpOMS\Account\PermissionType;
use phpOMS\Application\ApplicationAbstract;
use phpOMS\Asset\AssetType;
use phpOMS\Autoloader;
use phpOMS\Localization\BaseStringL11n;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\HttpResponse;
use phpOMS\Message\Http\RequestStatusCode;
use phpOMS\Message\NotificationLevel;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Model\Html\Head;
use phpOMS\Model\Message\FormValidation;
use phpOMS\Security\Guard;
use phpOMS\System\File\FileUtils;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\MimeType;
use phpOMS\Utils\ImageUtils;
use phpOMS\Utils\Parser\Markdown\Markdown;
use phpOMS\Utils\StringUtils;
use phpOMS\Views\View;

/**
 * Media class.
 *
 * @package Modules\Media
 * @license OMS License 2.0
 * @link    https://jingga.app
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
    public function apiMediaUpload(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        $uploads = $this->uploadFiles(
            names:              $request->getDataList('names'),
            fileNames:          $request->getDataList('filenames'),
            files:              $request->getFiles(),
            account:            $request->header->account,
            basePath:           __DIR__ . '/../../../Modules/Media/Files' . \urldecode($request->getDataString('path') ?? ''),
            virtualPath:        \urldecode($request->getDataString('virtualpath') ?? ''),
            password:           $request->getDataString('password') ?? '',
            encryptionKey:      $request->getDataString('encrypt') ?? '',
            pathSettings:       $request->getDataInt('pathsettings') ?? PathSettings::RANDOM_PATH, // IMPORTANT!!!
            hasAccountRelation: $request->getDataBool('link_account') ?? false,
            readContent:        $request->getDataBool('parse_content') ?? false,
            unit:               $request->getDataInt('unit')
        );

        $ids = [];
        foreach ($uploads as $file) {
            $ids[] = $file->getId();

            // add media types
            if (!empty($types = $request->getDataJson('types'))) {
                foreach ($types as $type) {
                    if (!isset($type['id'])) {
                        $request->setData('name', $type['name'], true);
                        $request->setData('title', $type['title'], true);
                        $request->setData('lang', $type['lang'] ?? null, true);

                        $internalResponse = new HttpResponse();
                        $this->apiMediaTypeCreate($request, $internalResponse, null);

                        if (!\is_array($data = $internalResponse->get($request->uri->__toString()))) {
                            continue;
                        }

                        $file->addMediaType($tId = $data['response']);
                    } else {
                        $file->addMediaType(new NullMediaType($tId = (int) $type['id']));
                    }

                    $this->createModelRelation(
                        $request->header->account,
                        $file->getId(),
                        $tId,
                        MediaMapper::class,
                        'types',
                        '',
                        $request->getOrigin()
                    );
                }
            }

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

                        if (!\is_array($data = $internalResponse->get($request->uri->__toString()))) {
                            continue;
                        }

                        $file->addTag($tId = $data['response']);
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
     * Upload a media file and replace the existing media file
     *
     * @param array $files              Files
     * @param array $media              Media files to update
     * @param bool  $sameNameIfPossible Use exact same file name as original file name if the extension is the same.
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function replaceUploadFiles(
        array $files,
        array $media,
        bool $sameNameIfPossible = false
    ) : array
    {
        if (empty($files) || \count($files) !== \count($media)) {
            return [];
        }

        $nCounter = -1;
        foreach ($files as $file) {
            ++$nCounter;

            // set output dir same as existing media
            $outputDir = \dirname($media[$nCounter]->getAbsolutePath());

            // set upload name (either same as old file name or new file name)
            $mediaFilename  = \basename($media[$nCounter]->getAbsolutePath());
            $uploadFilename = \basename($file['tmp_name']);

            $splitMediaFilename  = \explode('.', $mediaFilename);
            $splitUploadFilename = \explode('.', $uploadFilename);

            $mediaExtension  =  ($c = \count($splitMediaFilename)) > 1 ? $splitMediaFilename[$c - 1] : '';
            $uploadExtension =  ($c = \count($splitUploadFilename)) > 1 ? $splitUploadFilename[$c - 1] : '';

            if ($sameNameIfPossible && $mediaExtension === $uploadExtension) {
                $uploadFilename = $mediaFilename;
            }

            // remove old file
            \unlink($media[$nCounter]->getAbsolutePath());

            // upload file
            $upload                   = new UploadFile();
            $upload->outputDir        = $outputDir;
            $upload->preserveFileName = $sameNameIfPossible;

            $status = $upload->upload([$file], [$uploadFilename], true);
            $stat   = \reset($status);

            // update media data
            $media[$nCounter]->setPath(self::normalizeDbPath($stat['path']) . '/' . $stat['filename']);
            $media[$nCounter]->size      = $stat['size'];
            $media[$nCounter]->extension = $stat['extension'];

            MediaMapper::update()->execute($media[$nCounter]);

            if (!empty($media[$nCounter]?->content->content)) {
                $media[$nCounter]->content->content = self::loadFileContent(
                    $media[$nCounter]->getAbsolutePath(),
                    $media[$nCounter]->extension
                );

                MediaContentMapper::update()->execute($media[$nCounter]->content);
            }
        }

        return $media;
    }

    /**
     * Upload a media file
     *
     * @param array  $names              Database names
     * @param array  $fileNames          FileNames
     * @param array  $files              Files
     * @param int    $account            Uploader
     * @param string $basePath           Base path. The path which is used for the upload.
     * @param string $virtualPath        virtual path The path which is used to visually structure the files, like directories
     *                                   The file storage on the system can be different
     * @param string $password           File password. The password to protect the file (only database)
     * @param string $encryptionKey      Encryption key. Used to encrypt the file on the local file storage.
     * @param int    $pathSettings       Settings which describe where the file should be uploaded to (physically)
     *                                   - RANDOM_PATH = random location in the base path
     *                                   - FILE_PATH   = combination of base path and virtual path
     * @param bool   $hasAccountRelation The uploaded files should be related to an account
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function uploadFiles(
        array $names = [],
        array $fileNames = [],
        array $files = [],
        int $account = 0,
        string $basePath = '/Modules/Media/Files',
        string $virtualPath = '',
        string $password = '',
        string $encryptionKey = '',
        int $pathSettings = PathSettings::RANDOM_PATH,
        bool $hasAccountRelation = true,
        bool $readContent = false,
        int $unit = null
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

        $upload                   = new UploadFile();
        $upload->outputDir        = $outputDir;
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
                app: $hasAccountRelation ? $this->app : null,
                readContent: $readContent,
                unit: $unit
            );
        }

        return $created;
    }

    /**
     * Uploads a file to a destination
     *
     * @param array  $files            Files to upload
     * @param array  $fileNames        Names on the directory
     * @param string $path             Upload path
     * @param bool   $preserveFileName Preserve file name
     *
     * @return array
     *
     * @since 1.0.0
     */
    public static function uploadFilesToDestination(
        array $files,
        array $fileNames = [],
        string $path = '',
        bool $preserveFileName = true
    ) : array
    {
        $upload                   = new UploadFile();
        $upload->outputDir        = $path; //empty($path) ? $upload->outputDir : $path;
        $upload->preserveFileName = $preserveFileName;

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
     * @param array                    $status      Files
     * @param int                      $account     Uploader
     * @param string                   $virtualPath Virtual path (not on the hard-drive)
     * @param string                   $ip          Ip of the origin
     * @param null|ApplicationAbstract $app         Should create relation to uploader
     * @param bool                     $readContent Should the content of the file be stored in the db
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public static function createDbEntry(
        array $status,
        int $account,
        string $virtualPath = '',
        string $ip = '127.0.0.1',
        ApplicationAbstract $app = null,
        bool $readContent = false,
        int $unit = null
    ) : Media
    {
        if (!isset($status['status']) || $status['status'] !== UploadStatus::OK) {
            return new NullMedia();
        }

        $media = new Media();

        $media->setPath(self::normalizeDbPath($status['path']) . '/' . $status['filename']);
        $media->name      = empty($status['name']) ? $status['filename'] : $status['name'];
        $media->size      = $status['size'];
        $media->createdBy = new NullAccount($account);
        $media->extension = $status['extension'];
        $media->unit      = $unit;
        $media->setVirtualPath($virtualPath);

        if ($readContent && \is_file($media->getAbsolutePath())) {
            $content = self::loadFileContent($media->getAbsolutePath(), $media->extension);

            if (!empty($content)) {
                $media->content          = new MediaContent();
                $media->content->content = $content;
            }
        }

        $app?->eventManager->triggerSimilar('PRE:Module:Media-media-create', '', $media);
        MediaMapper::create()->execute($media);
        $app?->eventManager->triggerSimilar('POST:Module:Media-media-create', '',
            [
                $account,
                null, $media,
                StringUtils::intHash(MediaMapper::class), 'Media-media-create',
                self::NAME,
                (string) $media->getId(),
                '',
                $ip
            ]
        );

        $app?->moduleManager->get('Admin')->createAccountModelPermission(
            new AccountPermission(
                $account,
                $app->unitId,
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

        return $media;
    }

    /**
     * Load the text content of a file
     *
     * @param string $path      Path of the file
     * @param string $extension File extension
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function loadFileContent(string $path, string $extension, string $output = 'html') : string
    {
        switch ($extension) {
            case 'pdf':
                return \phpOMS\Utils\Parser\Pdf\PdfParser::pdf2text($path/*, __DIR__ . '/../../../Tools/OCRImageOptimizer/bin/OCRImageOptimizerApp'*/);
            case 'doc':
            case 'docx':
                $include = \realpath(__DIR__ . '/../../../Resources/');
                if ($include === false) {
                    return '';
                }

                if (!Autoloader::inPaths($include)) {
                    Autoloader::addPath($include);
                }

                return \phpOMS\Utils\Parser\Document\DocumentParser::parseDocument($path, $output);
            case 'ppt':
            case 'pptx':
                $include = \realpath(__DIR__ . '/../../../Resources/');
                if ($include === false) {
                    return '';
                }

                if (!Autoloader::inPaths($include)) {
                    Autoloader::addPath($include);
                }

                return \phpOMS\Utils\Parser\Presentation\PresentationParser::parsePresentation($path, $output);
            case 'xls':
            case 'xlsx':
                $include = \realpath(__DIR__ . '/../../../Resources/');
                if ($include === false) {
                    return '';
                }

                if (!Autoloader::inPaths($include)) {
                    Autoloader::addPath($include);
                }

                return \phpOMS\Utils\Parser\Spreadsheet\SpreadsheetParser::parseSpreadsheet($path, $output);
            case 'txt':
            case 'md':
                $contents = \file_get_contents($path);

                return $contents === false ? '' : $contents;
            default:
                return '';
        }
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
    public function apiMediaUpdate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        /** @var Media $old */
        $old = MediaMapper::get()->where('id', (int) $request->getData('id'))->execute();

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
            $media->extension = $name[\count($name) - 1] ?? '';
            $media->setVirtualPath(\dirname($path));
            $media->setPath('/Modules/Media/Files/' . \ltrim($path, '\\/'));
            $media->isAbsolute = false;
        }

        if ($request->hasData('content')) {
            \file_put_contents(
                $media->isAbsolute ? $media->getPath() : __DIR__ . '/../../../' . \ltrim($media->getPath(), '\\/'),
                $request->getData('content')
            );

            $media->size = \strlen($request->getData('content'));
        }

        return $media;
    }

    /**
     * Api method to create a reference.
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
    public function apiReferenceCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        if (!empty($val = $this->validateReferenceCreate($request))) {
            $response->set('collection_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $ref = $this->createReferenceFromRequest($request);
        $this->createModel($request->header->account, $ref, ReferenceMapper::class, 'media_reference', $request->getOrigin());

        // get parent collection
        // create relation
        $parentCollectionId = (int) $request->getData('parent');
        if ($parentCollectionId === 0) {
            /** @var Collection $parentCollection */
            $parentCollection = CollectionMapper::get()
                ->where('virtualPath', \dirname($request->getDataString('virtualpath') ?? ''))
                ->where('name', \basename($request->getDataString('virtualpath') ?? ''))
                ->execute();

            $parentCollectionId = $parentCollection->getId();
        }

        if (!$request->hasData('source')) {
            $child = MediaMapper::get()
                ->where('virtualPath', \dirname($request->getData('child')))
                ->where('name', \basename($request->getData('child')))
                ->execute();

            $request->setData('source', $child->getId());
        }

        $this->createModelRelation(
            $request->header->account,
            $parentCollectionId,
            $ref->getId(),
            CollectionMapper::class,
            'sources',
            '',
            $request->getOrigin()
        );

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Reference', 'Reference successfully created.', $ref);
    }

    /**
     * Method to create a reference from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return Reference Returns the collection from the request
     *
     * @since 1.0.0
     */
    private function createReferenceFromRequest(RequestAbstract $request) : Reference
    {
        $mediaReference            = new Reference();
        $mediaReference->name      = \basename($request->getDataString('virtualpath') ?? '/');
        $mediaReference->source    = new NullMedia((int) $request->getData('source'));
        $mediaReference->createdBy = new NullAccount($request->header->account);
        $mediaReference->setVirtualPath(\dirname($request->getDataString('virtualpath') ?? '/'));

        return $mediaReference;
    }

    /**
     * Validate reference create request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool> Returns the validation array of the request
     *
     * @since 1.0.0
     */
    private function validateReferenceCreate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['parent'] = (empty($request->getData('parent')) && empty($request->getData('virtualpath'))))
            || ($val['source'] = (empty($request->getData('source')) && empty($request->getData('child'))))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to add an element to a collection.
     *
     * Very similar to create Reference
     * Reference = it's own media element which points to another element (disadvantage = additional step)
     * Collection add = directly pointing to other media element (disadvantage = we don't know if we are allowed to modify/delete)
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
    public function apiCollectionAdd(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        $collection = (int) $request->getData('collection');
        $media      = $request->getDataJson('media-list');

        foreach ($media as $file) {
            $this->createModelRelation(
                $request->header->account,
                $collection,
                $file,
                CollectionMapper::class,
                'sources',
                '',
                $request->getOrigin()
            );
        }
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
    public function apiCollectionCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
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
        $mediaCollection->name           = $request->getDataString('name') ?? '';
        $mediaCollection->description    = ($description = Markdown::parse($request->getDataString('description') ?? ''));
        $mediaCollection->descriptionRaw = $description;
        $mediaCollection->createdBy      = new NullAccount($request->header->account);

        $media = $request->getDataJson('media-list');
        foreach ($media as $file) {
            $mediaCollection->addSource(new NullMedia((int) $file));
        }

        $virtualPath = \urldecode((string) ($request->getData('virtualpath') ?? '/'));

        $outputDir = '';
        $basePath  = __DIR__ . '/../../../Modules/Media/Files';
        if (empty($request->getData('path'))) {
            $outputDir = self::createMediaPath($basePath);
        } else {
            $outputDir = $basePath . '/' . \ltrim($request->getData('path'), '\\/');
        }

        $dirPath   = $outputDir . '/' . $request->getData('name');
        $outputDir = \substr($outputDir, \strlen(__DIR__ . '/../../..'));

        $mediaCollection->setVirtualPath($virtualPath);
        $mediaCollection->setPath($outputDir);

        if (((bool) ($request->getData('create_directory') ?? false))
            && !\is_dir($dirPath)
        ) {
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
                PermissionType::CREATE, $this->app->unitId, null, self::NAME, PermissionCategory::COLLECTION, null)
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
     * Create a collection recursively
     *
     * The function also creates all parent collections if they don't exist
     *
     * @param string $path         Virtual path of the collection
     * @param int    $account      Account who creates this collection
     * @param string $physicalPath The physical path where the corresponding directory should be created
     *
     * @return Collection
     *
     * @since 1.0.0
     */
    public function createRecursiveMediaCollection(string $path, int $account, string $physicalPath = '') : Collection
    {
        $status = false;
        if (!empty($physicalPath)) {
            $status = !\is_dir($physicalPath) ? \mkdir($physicalPath, 0755, true) : true;
        }

        $path      = \trim($path, '/');
        $paths     = \explode('/', $path);
        $tempPaths = $paths;
        $length    = \count($paths);

        /** @var Collection $parentCollection */
        $parentCollection = null;

        $temp = '';
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
            $childCollection            = new Collection();
            $childCollection->name      = $paths[$i];
            $childCollection->createdBy = new NullAccount($account);
            $childCollection->setVirtualPath('/'. \ltrim($temp, '/'));
            $childCollection->setPath('/Modules/Media/Files' . $temp);

            $this->createModel($account, $childCollection, CollectionMapper::class, 'collection', '127.0.0.1');
            $this->createModelRelation(
                $account,
                $parentCollection->getId(),
                $childCollection->getId(),
                CollectionMapper::class,
                'sources',
                '',
                '127.0.0.1'
            );

            $parentCollection = $childCollection;
            $temp            .= '/' . $paths[$i];
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
    public function apiMediaCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        $path        = \urldecode($request->getDataString('path') ?? '');
        $virtualPath = \urldecode((string) ($request->getData('virtualpath') ?? '/'));
        $fileName    = (string) ($request->getData('filename') ?? ($request->getDataString('name') ?? ''));
        $fileName   .= \strripos($fileName, '.') === false ? '.txt' : '';

        $outputDir = '';
        $basePath  = __DIR__ . '/../../../Modules/Media/Files';
        if (empty($request->getData('path'))) {
            $outputDir = self::createMediaPath($basePath);
        } else {
            if (\stripos(
                    FileUtils::absolute($basePath . '/' . \ltrim($path, '\\/')),
                    FileUtils::absolute(__DIR__ . '/../../../')
                ) !== 0
            ) {
                $outputDir = self::createMediaPath($basePath);
            } else {
                $outputDir = $basePath . '/' . \ltrim($path, '\\/');
            }
        }

        if (!\is_dir($outputDir)) {
            $created = Directory::create($outputDir, 0775, true);

            if (!$created) {
                throw new \Exception('Couldn\'t create outputdir: "' . $outputDir . '"'); // @codeCoverageIgnore
            }
        }

        \file_put_contents($outputDir . '/' . $fileName, $request->getDataString('content') ?? '');
        $outputDir = \substr($outputDir, \strlen(__DIR__ . '/../../..'));

        $status = [
            [
                'status'    => UploadStatus::OK,
                'path'      => $outputDir,
                'filename'  => $fileName,
                'name'      => $request->getDataString('name') ?? '',
                'size'      => \strlen($request->getDataString('content') ?? ''),
                'extension' => \substr($fileName, \strripos($fileName, '.') + 1),
            ],
        ];

        $ids = [];
        foreach ($status as $stat) {
            $created = self::createDbEntry(
                $stat,
                $request->header->account,
                $virtualPath,
                $request->getOrigin(),
                $this->app,
                unit: $request->getDataInt('unit')
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
    public function apiMediaExport(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        $filePath = '';
        $media    = null;

        if ($request->hasData('id')) {
            /** @var Media $media */
            $media    = MediaMapper::get()->where('id', (int) $request->getData('id'))->execute();
            $filePath = $media->getAbsolutePath();
        } else {
            $path  = \urldecode($request->getData('path'));
            $media = new NullMedia();

            if (\is_file($filePath = __DIR__ . '/../../../' . \ltrim($path, '\\/'))) {
                $name = \explode('.', \basename($path));

                $media->name       = $name[0];
                $media->extension  = $name[\count($name) - 1] ?? '';
                $media->isAbsolute = false;
                $media->setVirtualPath(\dirname($path));
                $media->setPath('/' . \ltrim($path, '\\/'));
            }
        }

        if (!($media instanceof NullMedia)) {
            if ($request->header->account !== $media->createdBy->getId()
                && !$this->app->accountManager->get($request->header->account)->hasPermission(
                    PermissionType::READ,
                    $this->app->unitId,
                    $this->app->appName,
                    self::NAME,
                    PermissionCategory::MEDIA,
                    $media->getId()
                )
            ) {
                $this->fillJsonResponse($request, $response, NotificationLevel::HIDDEN, '', '', []);
                $response->header->status = RequestStatusCode::R_403;

                return;
            }

            if (!isset($data, $data['guard'])) {
                if (!isset($data)) {
                    $data = [];
                }

                $data['guard'] = __DIR__ . '/../Files';
            }
        } else {
            if (!isset($data, $data['guard'])) {
                $this->fillJsonResponse($request, $response, NotificationLevel::HIDDEN, '', '', []);
                $response->header->status = RequestStatusCode::R_403;
            }
        }

        if (!Guard::isSafePath($filePath, $data['guard'])) {
            $response->header->status = RequestStatusCode::R_403;

            return;
        }

        if ($media->hasPassword()
            && !$media->comparePassword((string) $request->getData('password'))
        ) {
            $view = new View($this->app->l11nManager, $request, $response);
            $view->setTemplate('/Modules/Media/Theme/Api/invalidPassword');

            return;
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

            $head->addAsset(AssetType::CSS, 'cssOMS/styles.css?v=1.0.0');
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
    public function apiMediaTypeCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
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
     * @return MediaType
     *
     * @since 1.0.0
     */
    private function createDocTypeFromRequest(RequestAbstract $request) : MediaType
    {
        $type       = new MediaType();
        $type->name = $request->getDataString('name') ?? '';

        if (!empty($request->getData('title'))) {
            $type->setL11n($request->getDataString('title') ?? '', $request->getData('lang') ?? $request->getLanguage());
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
    public function apiMediaTypeL11nCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : void
    {
        if (!empty($val = $this->validateMediaTypeL11nCreate($request))) {
            $response->set('media_type_l11n_create', new FormValidation($val));
            $response->header->status = RequestStatusCode::R_400;

            return;
        }

        $l11nMediaType = $this->createMediaTypeL11nFromRequest($request);
        $this->createModel($request->header->account, $l11nMediaType, MediaTypeL11nMapper::class, 'media_type_l11n', $request->getOrigin());

        $this->fillJsonResponse($request, $response, NotificationLevel::OK, 'Localization', 'Localization successfully created', $l11nMediaType);
    }

    /**
     * Method to create tag localization from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return BaseStringL11n
     *
     * @since 1.0.0
     */
    private function createMediaTypeL11nFromRequest(RequestAbstract $request) : BaseStringL11n
    {
        $l11nMediaType          = new BaseStringL11n();
        $l11nMediaType->ref     = $request->getDataInt('type') ?? 0;
        $l11nMediaType->content = $request->getDataString('title') ?? '';
        $l11nMediaType->setLanguage(
            $request->getDataString('language') ?? $request->getLanguage()
        );

        return $l11nMediaType;
    }

    /**
     * Resize image file
     *
     * @param Media $media  Media object
     * @param int   $width  New width
     * @param int   $height New height
     * @param bool  $crop   Crop image instead of resizing
     *
     * @return Media
     * @since 1.0.0
     */
    public function resizeImage(
        Media $media,
        int $width,
        int $height,
        bool $crop = false) : Media {
        ImageUtils::resize(
            $media->getAbsolutePath(),
            $media->getAbsolutePath(),
            $width,
            $height,
            $crop
        );

        $temp        = \filesize($media->getAbsolutePath());
        $media->size = $temp === false ? 0 : $temp;

        return $media;
    }
}
