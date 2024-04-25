<?php
/**
 * Jingga
 *
 * PHP Version 8.2
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
use Modules\Media\Models\MediaClass;
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
use Modules\Media\Theme\Backend\Components\Media\ElementView;
use Modules\Messages\Models\EmailMapper;
use phpOMS\Account\PermissionType;
use phpOMS\Ai\Ocr\Tesseract\TesseractOcr;
use phpOMS\Application\ApplicationAbstract;
use phpOMS\Asset\AssetType;
use phpOMS\Autoloader;
use phpOMS\Localization\BaseStringL11n;
use phpOMS\Localization\ISO639x1Enum;
use phpOMS\Message\Http\HttpResponse;
use phpOMS\Message\Http\RequestStatusCode;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Model\Html\Head;
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
     * Api method to create email from media
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaEmailSend(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        $email = $request->getDataString('email');
        if (empty($email)) {
            return;
        }

        $media = $data['media'] ?? MediaMapper::get()
            ->where('id', (int) $request->getData('id'))
            ->execute();

        /** @var \Model\Setting $template */
        $template = $this->app->appSettings->get(
            names: (string) $request->getDataString('template')
        );

        $handler = $this->app->moduleManager->get('Admin', 'Api')->setUpServerMailHandler();

        $mail = EmailMapper::get()
            ->with('l11n')
            ->where('id', $template)
            ->where('l11n/language', $response->header->l11n->language)
            ->execute();

        $status = false;
        if ($mail->id !== 0) {
            $status = $this->app->moduleManager->get('Admin', 'Api')->setupEmailDefaults($mail, $response->header->l11n->language);
        }

        $mail->addTo($email);
        $mail->addAttachment($media->getAbsolutePath(), $media->name);

        if ($status) {
            $status = $handler->send($mail);
        }

        if (!$status) {
            \phpOMS\Log\FileLogger::getInstance()->error(
                \phpOMS\Log\FileLogger::MSG_FULL, [
                    'message' => 'Couldn\'t send bill media: ' . $media->id,
                    'line'    => __LINE__,
                    'file'    => self::class,
                ]
            );
        }
    }

    /**
     * Api method to upload media file.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaUpload(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        $uploads = $this->uploadFiles(
            names:              $request->getDataList('names'),
            fileNames:          $request->getDataList('filenames'),
            files:              $request->files,
            account:            $request->header->account,
            basePath:           __DIR__ . '/../../../Modules/Media/Files' . \urldecode($request->getDataString('path') ?? ''),
            virtualPath:        \urldecode($request->getDataString('virtualpath') ?? ''),
            password:           $request->getDataString('password') ?? '',
            encryptionKey:      $request->getDataString('encryption') ?? ($request->getDataBool('isencrypted') === true && !empty($_SERVER['OMS_PRIVATE_KEY_I'] ?? '') ? $_SERVER['OMS_PRIVATE_KEY_I'] : ''),
            pathSettings:       $request->getDataInt('pathsettings') ?? PathSettings::RANDOM_PATH, // IMPORTANT!!!
            hasAccountRelation: $request->getDataBool('link_account') ?? false,
            readContent:        $request->getDataBool('parse_content') ?? false,
            unit:               $request->getDataInt('unit'),
            createCollection:   $request->getDataBool('create_collection') ?? false,
        );

        $ids = [];
        foreach ($uploads as $file) {
            $ids[] = $file->id;

            // add media types
            if (!empty($types = $request->getDataJson('types'))) {
                foreach ($types as $type) {
                    if (!isset($type['id'])) {
                        $request->setData('name', $type['name'], true);
                        $request->setData('title', $type['title'], true);
                        $request->setData('lang', $type['lang'] ?? null, true);

                        $internalResponse = new HttpResponse();
                        $this->apiMediaTypeCreate($request, $internalResponse);

                        if (!\is_array($data = $internalResponse->getDataArray($request->uri->__toString()))) {
                            continue;
                        }

                        $file->addMediaType($tId = $data['response']);
                    } else {
                        $file->addMediaType(new NullMediaType($tId = (int) $type['id']));
                    }

                    $this->createModelRelation(
                        $request->header->account,
                        $file->id,
                        $tId,
                        MediaMapper::class,
                        'types',
                        '',
                        $request->getOrigin()
                    );
                }
            }

            if ($request->hasData('tags')) {
                $file->tags = $this->app->moduleManager->get('Tag', 'Api')->createTagsFromRequest($request);
            }

            $this->createModelRelation(
                $request->header->account,
                $file->id,
                \array_map(function (\Modules\Tag\Models\Tag $tag) { return $tag->id; }, $file->tags),
                MediaMapper::class,
                'tags',
                '',
                $request->getOrigin()
            );
        }

        $this->createStandardAddResponse($request, $response, $ids);
    }

    /**
     * Upload a media file and replace the existing media file
     *
     * @param array $files              Files
     * @param array $media              Media files to update
     * @param bool  $sameNameIfPossible use exact same file name as original file name if the extension is the same
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

            $mediaExtension  = ($c = \count($splitMediaFilename)) > 1 ? $splitMediaFilename[$c - 1] : '';
            $uploadExtension = ($c = \count($splitUploadFilename)) > 1 ? $splitUploadFilename[$c - 1] : '';

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
     * @return Collection
     *
     * @since 1.0.0
     */
    public function uploadFiles(
        array $names = [],
        array $fileNames = [],
        array $files = [],
        int $account = 0,
        string $basePath = '',
        string $virtualPath = '',
        string $password = '',
        string $encryptionKey = '',
        int $pathSettings = PathSettings::RANDOM_PATH,
        bool $hasAccountRelation = true,
        bool $readContent = false,
        ?int $unit = null,
        bool $createCollection = true,
        ?int $type = null,
        ?int $rel = null,
        string $mapper = '',
        string $field = ''
    ) : Collection
    {
        if (empty($files)) {
            return new NullCollection();
        }

        $outputDir = '';
        $absolute  = false;

        if ($pathSettings === PathSettings::RANDOM_PATH) {
            $outputDir = self::createMediaPath($basePath);
        } elseif ($pathSettings === PathSettings::FILE_PATH) {
            $outputDir = \rtrim($basePath, '/\\');
            $absolute  = true;
        } else {
            return new NullCollection();
        }

        if (!Guard::isSafePath($outputDir, __DIR__ . '/../../../')) {
            return new NullCollection();
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

            $media = self::createDbEntry(
                $stat,
                $account,
                $virtualPath,
                app: $hasAccountRelation ? $this->app : null,
                readContent: $readContent,
                unit: $unit,
                password: $password,
                isEncrypted: !empty($encryptionKey)
            );

            // Create relation to type
            if (!empty($type)) {
                $this->createModelRelation($account, $media->id, $type, MediaMapper::class, 'types', '', '127.0.0.1');
            }

            // Create relation to model
            if (!empty($rel)) {
                $this->createModelRelation($account, $rel, $media->id, $mapper, $field, '', '127.0.0.1');
            }

            $created[] = $media;
        }

        if (!$createCollection) {
            $collection          = new NullCollection();
            $collection->sources = $created;

            return $collection;
        }

        $collection = $this->createRecursiveMediaCollection($virtualPath, $account, $basePath);
        foreach ($created as $media) {
            $this->createModelRelation($account, $collection->id, $media->id, CollectionMapper::class, 'sources', '','127.0.0.1');
            $collection->sources[] = $media;
        }

        return $collection;
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
        $rndPath = \bin2hex(\random_bytes(4));
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
        ?ApplicationAbstract $app = null,
        bool $readContent = false,
        ?int $unit = null,
        string $password = '',
        bool $isEncrypted = false
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
        $media->setPassword($password);
        $media->isEncrypted = $isEncrypted;

        // Store text from document in DB for later use e.g. full text search (uses OCR, text extraction etc. if necessary)
        if ($readContent && \is_file($media->getAbsolutePath())) {
            $content = self::loadFileContent($media->getAbsolutePath(), $media->extension);

            if (!empty($content)) {
                $media->content          = new MediaContent();
                $media->content->content = $content;
            }
        }

        if ($app === null) {
            MediaMapper::create()->execute($media);

            return $media;
        }

        $app->eventManager->triggerSimilar('PRE:Module:Media-media-create', '', $media);
        MediaMapper::create()->execute($media);
        $app->eventManager->triggerSimilar('POST:Module:Media-media-create', '',
            [
                $account,
                null, $media,
                StringUtils::intHash(MediaMapper::class), 'Media-media-create',
                self::NAME,
                (string) $media->id,
                '',
                $ip,
            ]
        );

        $app->moduleManager->get('Admin', 'Api')->createAccountModelPermission(
            new AccountPermission(
                $account,
                $app->unitId,
                $app->appId,
                self::NAME,
                self::NAME,
                PermissionCategory::MEDIA,
                $media->id,
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
    public static function loadFileContent(string $path, string $extension, string $output = 'html', array $data = []) : string
    {
        switch ($extension) {
            case 'pdf':
                return \phpOMS\Utils\Parser\Pdf\PdfParser::pdf2text($path/*, __DIR__ . '/../../../Tools/OCRImageOptimizer/bin/OCRImageOptimizerApp'*/);
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'tiff':
            case 'webp':
            case 'bmp':
                $ocr = new TesseractOcr();

                return $ocr->parseImage($path);
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
            case 'htm':
            case 'html':
                return \phpOMS\Utils\Parser\Html\HtmlParser::parseHtml($path, $output, $data['path'] ?? '');
            case 'xml':
                return \phpOMS\Utils\Parser\Xml\XmlParser::parseXml($path, $output, $data['path'] ?? '');
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
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public static function normalizeDbPath(string $path) : string
    {
        $realpath = \realpath(__DIR__ . '/../../../');
        if ($realpath === false) {
            throw new \Exception(); // @codeCoverageIgnore
        }

        return FileUtils::absolute(
            \str_replace(
                '\\',
                '/',
                \str_replace($realpath, '', \rtrim($path, '\\/'))
            )
        );
    }

    /**
     * Api method to update media.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaUpdate(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        if (!empty($val = $this->validateMediaUpdate($request))) {
            $response->header->status = RequestStatusCode::R_400;
            $this->createInvalidUpdateResponse($request, $response, $val);

            return;
        }

        /** @var Media $old */
        $old = MediaMapper::get()->where('id', (int) $request->getData('id'))->execute();
        $new = $this->updateMediaFromRequest($request, clone $old);

        $this->updateModel($request->header->account, $old, $new, MediaMapper::class, 'media', $request->getOrigin());
        $this->createStandardUpdateResponse($request, $response, $new);
    }

    /**
     * Validate media update request
     *
     * @param RequestAbstract $request Request
     *
     * @return array<string, bool> Returns the validation array of the request
     *
     * @since 1.0.0
     */
    private function validateMediaUpdate(RequestAbstract $request) : array
    {
        $val = [];
        if (($val['id'] = !$request->hasData('id'))) {
            return $val;
        }

        return [];
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
    private function updateMediaFromRequest(RequestAbstract $request, Media $new) : Media
    {
        $new->name        = $request->getDataString('name') ?? $new->name;
        $new->description = $request->getDataString('description') ?? $new->description;
        $new->setPath($request->getDataString('path') ?? $new->getPath());
        $new->setVirtualPath(\urldecode($request->getDataString('virtualpath') ?? $new->getVirtualPath()));

        if ($new->id === 0
            || !$this->app->accountManager->get($request->header->account)->hasPermission(
                PermissionType::MODIFY,
                $this->app->unitId,
                $this->app->appId,
                self::NAME,
                PermissionCategory::MEDIA,
                $request->header->account
            )
        ) {
            return $new;
        }

        if ($request->hasData('content')) {
            \file_put_contents(
                $new->isAbsolute
                    ? $new->getPath()
                    : __DIR__ . '/../../../' . \ltrim($new->getPath(), '\\/'),
                $request->getDataString('content') ?? ''
            );

            $new->size = \strlen($request->getDataString('content') ?? '');
        }

        return $new;
    }

    /**
     * Api method to create a reference.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiReferenceCreate(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        if (!empty($val = $this->validateReferenceCreate($request))) {
            $response->header->status = RequestStatusCode::R_400;
            $this->createInvalidCreateResponse($request, $response, $val);

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

            $parentCollectionId = $parentCollection->id;
        }

        if (!$request->hasData('source')) {
            /** @var \Modules\Media\Models\Media $child */
            $child = MediaMapper::get()
                ->where('virtualPath', \dirname($request->getDataString('child') ?? ''))
                ->where('name', \basename($request->getDataString('child') ?? ''))
                ->execute();

            $request->setData('source', $child->id);
        }

        $this->createModelRelation(
            $request->header->account,
            $parentCollectionId,
            $ref->id,
            CollectionMapper::class,
            'sources',
            '',
            $request->getOrigin()
        );

        $this->createStandardCreateResponse($request, $response, $ref);
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
        if (($val['parent'] = (!$request->hasData('parent') && !$request->hasData('virtualpath')))
            || ($val['source'] = (!$request->hasData('source') && !$request->hasData('child')))
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
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiCollectionAdd(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
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
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiCollectionCreate(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        if (!empty($val = $this->validateCollectionCreate($request))) {
            $response->header->status = RequestStatusCode::R_400;
            $this->createInvalidCreateResponse($request, $response, $val);

            return;
        }

        $collection = $this->createCollectionFromRequest($request);
        $this->createModel($request->header->account, $collection, CollectionMapper::class, 'collection', $request->getOrigin());
        $this->createStandardCreateResponse($request, $response, $collection);
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
        if (($val['name'] = !$request->hasData('name'))) {
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

        $virtualPath = \urldecode($request->getDataString('virtualpath') ?? '/');
        $basePath    = __DIR__ . '/../../../Modules/Media/Files';
        $outputDir   = $request->hasData('path')
            ? $basePath . '/' . \ltrim($request->getDataString('path') ?? '', '\\/')
            : self::createMediaPath($basePath);

        $dirPath   = $outputDir . '/' . ($request->getDataString('name') ?? '');
        $outputDir = \substr($outputDir, \strlen(__DIR__ . '/../../..'));

        $mediaCollection->setVirtualPath($virtualPath);
        $mediaCollection->setPath($outputDir);

        if (($request->getDataBool('create_directory') ?? false)
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
        $status = true;
        if (!empty($physicalPath)) {
            $status = \is_dir($physicalPath) ? true : \mkdir($physicalPath, 0755, true);
        }

        if (!$status) {
            $this->app->logger->error(\phpOMS\Log\FileLogger::MSG_FULL, [
                'message' => 'Couldn\'t create directory "' . $physicalPath . '"',
                'line'    => __LINE__,
                'file'    => self::class,
            ]);
        }

        $virtualPath      = \trim($path, '/');
        $virtualPaths     = \explode('/', $virtualPath);
        $tempVirtualPaths = $virtualPaths;
        $length           = \count($virtualPaths);

        /** @var Collection $parentCollection */
        $parentCollection = null;

        $virtual    = '';
        $real       = '';
        $newVirtual = '';

        for ($i = $length; $i > 0; --$i) {
            $virtual = '/' . \implode('/', $tempVirtualPaths);

            /** @var Collection $parentCollection */
            $parentCollection = CollectionMapper::get()
                ->where('virtualPath', \dirname($virtual))
                ->where('class', MediaClass::COLLECTION)
                ->where('name', \basename($virtual))
                ->limit(1)
                ->execute();

            if ($parentCollection->id > 0) {
                $real = \rtrim($parentCollection->path, '/') . '/' . $parentCollection->name;

                break;
            }

            $newVirtual = \array_pop($tempVirtualPaths) . '/' . $newVirtual;
        }

        for (; $i < $length; ++$i) {
            /* Create collection */
            $childCollection            = new Collection();
            $childCollection->name      = $virtualPaths[$i];
            $childCollection->createdBy = new NullAccount($account);
            $childCollection->setVirtualPath('/'. \trim($virtual, '/'));

            // We assume that the new path is real path of the first found parent directory + the new virtual path
            $childCollection->setPath(\rtrim($real, '/') . '/' . \trim($newVirtual, '/'));

            $this->createModel($account, $childCollection, CollectionMapper::class, 'collection', '127.0.0.1');
            $this->createModelRelation(
                $account,
                $parentCollection->id,
                $childCollection->id,
                CollectionMapper::class,
                'sources',
                '',
                '127.0.0.1'
            );

            $parentCollection = $childCollection;
            $virtual .= '/' . $virtualPaths[$i];
        }

        return $parentCollection;
    }

    /**
     * Add Media to a collection and arbitrary model
     *
     * @param int      $account        Request account
     * @param int[]    $files          Files to add
     * @param null|int $rel            Relation to model id
     * @param string   $mapper         Mapper to use for relation
     * @param string   $field          Field to use for relation
     * @param string   $collectionPath Path of the collection the files should get added to
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function addMediaToCollectionAndModel(
        int $account,
        array $files,
        ?int $rel = null, string $mapper = '', string $field = '',
        string $collectionPath = ''
    ) : void
    {
        /** @var \Modules\Media\Models\Media[] $mediaFiles */
        $mediaFiles = MediaMapper::getAll()
            ->where('id', $files)
            ->executeGetArray();

        $collection = null;

        foreach ($mediaFiles as $media) {
            if ($rel !== null) {
                $this->createModelRelation($account, $rel, $media->id, $mapper, $field, '', '127.0.0.1');
            }

            if (!empty($collectionPath)) {
                $ref            = new Reference();
                $ref->name      = $media->name;
                $ref->source    = new NullMedia($media->id);
                $ref->createdBy = new NullAccount($account);
                $ref->setVirtualPath($collectionPath);

                $this->createModel($account, $ref, ReferenceMapper::class, 'media_reference', '127.0.0.1');

                if ($collection === null) {
                    /** @var \Modules\Media\Models\Collection $collection */
                    $collection = CollectionMapper::get()
                        ->where('virtualPath', \dirname($collectionPath))
                        ->where('class', MediaClass::COLLECTION)
                        ->where('name', \basename($collectionPath))
                        ->limit(1)
                        ->execute();

                    if ($collection->id === 0) {
                        $collection = $this->app->moduleManager->get('Media', 'Api')->createRecursiveMediaCollection(
                            $collectionPath,
                            $account,
                            __DIR__ . '/../../../Modules/Media/Files' . $collectionPath
                        );
                    }
                }

                $this->createModelRelation($account, $collection->id, $ref->id, CollectionMapper::class, 'sources', '', '127.0.0.1');
            }
        }
    }

    /**
     * Api method to create media file.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function apiMediaCreate(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        $path        = \urldecode($request->getDataString('path') ?? '');
        $virtualPath = \urldecode($request->getDataString('virtualpath') ?? '/');
        $fileName    = $request->getDataString('filename') ?? ($request->getDataString('name') ?? '');
        $fileName .= \strripos($fileName, '.') === false ? '.txt' : '';

        $outputDir = '';
        $basePath  = __DIR__ . '/../../../Modules/Media/Files';
        if (!$request->hasData('path')) {
            $outputDir = self::createMediaPath($basePath);
        } elseif (\stripos(
                FileUtils::absolute($basePath . '/' . \ltrim($path, '\\/')),
                FileUtils::absolute(__DIR__ . '/../../../')
            ) !== 0) {
            $outputDir = self::createMediaPath($basePath);
        } else {
            $outputDir = $basePath . '/' . \ltrim($path, '\\/');
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
                unit: $request->getDataInt('unit'),
                password: $request->getDataString('password') ?? '',
                isEncrypted: $request->getDataBool('isencrypted') ?? $request->hasData('encryption')
            );

            $ids[] = $created->id;
        }

        $this->createStandardAddResponse($request, $response, $ids);
    }

    /**
     * Routing end-point for application behavior.
     *
     * @param \phpOMS\Message\Http\HttpRequest $request  Request
     * @param HttpResponse                     $response Response
     * @param array                            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaExport(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        $filePath = '';
        $media    = null;

        if ($request->hasData('id')) {
            /** @var Media $media */
            $media    = MediaMapper::get()->where('id', (int) $request->getData('id'))->execute();
            $filePath = $media->getAbsolutePath();
        } else {
            $path  = \urldecode($request->getDataString('path') ?? '');
            $media = new NullMedia();

            if (\is_file($filePath = __DIR__ . '/../../../' . \ltrim($path, '\\/'))) {
                $name = \explode('.', \basename($path));

                $media->name       = $name[0];
                $media->extension  = $name[\count($name) - 1] ?? '';
                $media->isAbsolute = false;
                $media->setVirtualPath(\dirname($path));
                $media->setPath('/' . \ltrim($path, '\\/'));
            } else {
                /** @var Media $media */
                $media = MediaMapper::get()
                    ->where('virtualPath', $path)
                    ->limit(1)
                    ->execute();

                $filePath = $media->getAbsolutePath();
            }
        }

        if ($media->id > 0) {
            if (!($data['ignorePermission'] ?? false)
                && $request->header->account !== $media->createdBy->id
                && !$this->app->accountManager->get($request->header->account)->hasPermission(
                    PermissionType::READ,
                    $this->app->unitId,
                    $this->app->appId,
                    self::NAME,
                    PermissionCategory::MEDIA,
                    $media->id
                )
            ) {
                $response->header->status = RequestStatusCode::R_403;
                $this->createInvalidReturnResponse($request, $response, $media);

                return;
            }

            if (!isset($data['guard'])) {
                $data['guard'] = __DIR__ . '/../Files';
            }
        } elseif (empty($data) || !isset($data['guard'])) {
            $response->header->status = RequestStatusCode::R_403;
            $this->createInvalidReturnResponse($request, $response, $media);
        }

        if (!Guard::isSafePath($filePath, $data['guard'] ?? '')) {
            $response->header->status = RequestStatusCode::R_403;

            return;
        }

        if ($media->hasPassword()
            && !$media->comparePassword($request->getDataString('password') ?? '')
        ) {
            $view = new View($this->app->l11nManager, $request, $response);
            $view->setTemplate('/Modules/Media/Theme/Api/invalidPassword');

            return;
        }

        if ($media->isEncrypted) {
            $media = $this->prepareEncryptedMedia($media, $request);

            if ($media->id === 0) {
                $response->header->status = RequestStatusCode::R_500;
                $this->createInvalidReturnResponse($request, $response, $media);

                return;
            }
        }

        if ($media->extension !== 'collection' && !\is_file($media->getAbsolutePath())) {
            $response->header->status = RequestStatusCode::R_500;
            $this->createInvalidReturnResponse($request, $response, $media);

            return;
        }

        $this->setMediaResponseHeader($media, $request, $response);
        $view               = $this->createView($media, $request, $response);
        $view->data['path'] = __DIR__ . '/../../../';

        $response->set('export', $view);
    }

    /**
     * Decrypt an encrypted media element
     *
     * @param Media           $media   Media model
     * @param RequestAbstract $request Request model
     *
     * @return Media
     *
     * @since 1.0.0
     */
    private function prepareEncryptedMedia(Media $media, RequestAbstract $request) : Media
    {
        $path         = '';
        $absolutePath = '';

        $counter = 0;
        do {
            $randomName = \sha1(\random_bytes(32));

            $path         = '../../../Temp/' . $randomName . '.' . $media->getExtension();
            $absolutePath = __DIR__ . '/' . $path;

            ++$counter;
        } while (!\is_file($absolutePath) && $counter < 1000);

        if ($counter >= 1000) {
            return new NullMedia();
        }

        $encryptionKey = $request->getDataBool('isencrypted') === true && !empty($_SESSION['OMS_PRIVATE_KEY_I'] ?? '')
            ? $_SESSION['OMS_PRIVATE_KEY_I']
            : $request->getDataString('encrpkey') ?? '';

        $decrypted = $media->decrypt($encryptionKey, $absolutePath);

        if (!$decrypted) {
            return new NullMedia();
        }

        $media->path = $media->isAbsolute ? $absolutePath : $path;

        return $media;
    }

    /**
     * Routing end-point for application behavior.
     *
     * @param Media                            $media    Media
     * @param \phpOMS\Message\Http\HttpRequest $request  Request
     * @param HttpResponse                     $response Response
     *
     * @return View
     *
     * @since 1.0.0
     */
    public function createView(Media $media, RequestAbstract $request, ResponseAbstract $response) : View
    {
        $view        = new ElementView($this->app->l11nManager, $request, $response);
        $view->media = $media;

        if (!\headers_sent()) {
            $response->endAllOutputBuffering(); // for large files
        }

        if (\in_array($type = $request->getDataString('type'), [null, 'download', 'raw', 'bin'])) {
            $view->setTemplate('/Modules/Media/Theme/Api/render');
        } elseif ($type === 'html') {
            $head = new Head();

            $css = '';
            if (\is_file(__DIR__ . '/../../../Web/Backend/css/backend-small.css')) {
                $css = \file_get_contents(__DIR__ . '/../../../Web/Backend/css/backend-small.css');

                if ($css === false) {
                    $css = ''; // @codeCoverageIgnore
                }
            }

            $css = \preg_replace('!\s+!', ' ', $css);
            $head->setStyle('core', $css ?? '');

            $head->addAsset(AssetType::CSS, 'cssOMS/styles.css?v=' . self::VERSION);
            $view->data['head'] = $head;

            switch (\strtolower($media->extension)) {
                case 'jpg':
                case 'jpeg':
                case 'gif':
                case 'png':
                case 'webp':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/image_raw');
                    break;
                case 'pdf':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/pdf_raw');
                    break;
                case 'c':
                case 'cpp':
                case 'h':
                case 'php':
                case 'js':
                case 'css':
                case 'csv':
                case 'rs':
                case 'py':
                case 'r':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/text_raw');
                    break;
                case 'json':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/json_raw');
                    break;
                case 'txt':
                case 'cfg':
                case 'log':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/text_raw');
                    break;
                case 'md':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/markdown_raw');
                    break;
                case 'xls':
                case 'xlsx':
                    $view->setTemplate('/Modules/Media/Theme/Api/spreadsheetAsHtml');
                    break;
                case 'xml':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/xml_raw');
                    break;
                case 'htm':
                case 'html':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/html_raw');
                    break;
                case 'doc':
                case 'docx':
                    $view->setTemplate('/Modules/Media/Theme/Api/wordAsHtml');
                    break;
                case 'mp3':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/audio_raw');
                    break;
                case 'mp4':
                case 'mpeg':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/video_raw');
                    break;
                case 'collection':
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/collection_raw');
                    break;
                default:
                    $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/default');
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
        switch ($request->getDataString('type') ?? \strtolower($media->extension)) {
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
                $response->header->set('Content-Type', MimeType::M_CSV, true);
                break;
            case 'json':
                $response->header->set('Content-Type', MimeType::M_JSON, true);
                break;
            case 'xls':
                $response->header->set('Content-Type', MimeType::M_XLS, true);
                break;
            case 'xlsx':
                $response->header->set('Content-Type', MimeType::M_XLSX, true);
                break;
            case 'xml':
                $response->header->set('Content-Type', MimeType::M_XML, true);
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
            case 'webp':
                $response->header->set('Content-Type', MimeType::M_WEBP, true);
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
                $response->header->set('Content-Disposition', 'attachment; filename="' . \addslashes($media->name) . '"', true);
                $response->header->set('Content-Transfer-Encoding', 'binary', true);
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
        if (($val['name'] = !$request->hasData('name'))
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
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaTypeCreate(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        if (!empty($val = $this->validateMediaTypeCreate($request))) {
            $response->header->status = RequestStatusCode::R_400;
            $this->createInvalidCreateResponse($request, $response, $val);

            return;
        }

        $type = $this->createDocTypeFromRequest($request);
        $this->createModel($request->header->account, $type, MediaTypeMapper::class, 'doc_type', $request->getOrigin());
        $this->createStandardCreateResponse($request, $response, $type);
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

        if ($request->hasData('title')) {
            $type->setL11n(
                $request->getDataString('title') ?? '',
                ISO639x1Enum::tryFromValue($request->getDataString('lang')) ?? $request->header->l11n->language
            );
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
        if (($val['title'] = !$request->hasData('title'))
            || ($val['type'] = !$request->hasData('type'))
        ) {
            return $val;
        }

        return [];
    }

    /**
     * Api method to create media type localization
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function apiMediaTypeL11nCreate(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        if (!empty($val = $this->validateMediaTypeL11nCreate($request))) {
            $response->header->status = RequestStatusCode::R_400;
            $this->createInvalidCreateResponse($request, $response, $val);

            return;
        }

        $l11nMediaType = $this->createMediaTypeL11nFromRequest($request);
        $this->createModel($request->header->account, $l11nMediaType, MediaTypeL11nMapper::class, 'media_type_l11n', $request->getOrigin());
        $this->createStandardCreateResponse($request, $response, $l11nMediaType);
    }

    /**
     * Method to create media type localization from request.
     *
     * @param RequestAbstract $request Request
     *
     * @return BaseStringL11n
     *
     * @since 1.0.0
     */
    private function createMediaTypeL11nFromRequest(RequestAbstract $request) : BaseStringL11n
    {
        $l11nMediaType           = new BaseStringL11n();
        $l11nMediaType->ref      = $request->getDataInt('type') ?? 0;
        $l11nMediaType->content  = $request->getDataString('title') ?? '';
        $l11nMediaType->language = ISO639x1Enum::tryFromValue($request->getDataString('language')) ?? $request->header->l11n->language;

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
