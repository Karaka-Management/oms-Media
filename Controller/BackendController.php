<?php
/**
 * Jingga
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

use Model\SettingMapper;
use Modules\Admin\Models\Account;
use Modules\Admin\Models\PermissionAbstractMapper;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaClass;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\MediaTypeL11nMapper;
use Modules\Media\Models\MediaTypeMapper;
use Modules\Media\Models\NullMedia;
use Modules\Media\Models\PermissionCategory;
use Modules\Media\Theme\Backend\Components\Media\ElementView;
use Modules\Media\Theme\Backend\Components\Media\ListView;
use Modules\Media\Views\MediaView;
use phpOMS\Account\PermissionType;
use phpOMS\Contract\RenderableInterface;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Utils\StringUtils;
use phpOMS\Views\View;

/**
 * Media class.
 *
 * @package Modules\Media
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 * @codeCoverageIgnore
 */
final class BackendController extends Controller
{
    use FileUploaderTrait;

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewMediaList(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-list');

        $path = \str_replace('+', ' ', $request->getDataString('path') ?? '/');

        $hasPermission = $this->app->accountManager->get($request->header->account)
            ->hasPermission(
                PermissionType::READ,
                $this->app->unitId,
                $this->app->appId,
                self::NAME,
                PermissionCategory::MEDIA,
            );

        $mediaMapper = MediaMapper::getByVirtualPath($path)->where('tags/title/language', $request->header->l11n->language);

        if (!$hasPermission) {
            $permWhere = PermissionAbstractMapper::helper($this->app->dbPool->get('select'))
                ->groups($this->app->accountManager->get($request->header->account)->getGroupIds())
                ->account($request->header->account)
                ->units([null, $this->app->unitId])
                ->apps([null, 'Api', $this->app->appId])
                ->modules([null, self::NAME])
                ->categories([null, PermissionCategory::MEDIA])
                ->permission(PermissionType::READ)
                ->query(MediaMapper::PRIMARYFIELD);

                $mediaMapper->where('', $permWhere);
        }

        /** @var Media[] $media */
        $media = $mediaMapper->execute();

        $collectionMapper = CollectionMapper::getParentCollection($path)
            ->where('tags/title/language', $request->header->l11n->language);

        if (!$hasPermission) {
            $permWhere = PermissionAbstractMapper::helper($this->app->dbPool->get('select'))
                ->groups($this->app->accountManager->get($request->header->account)->getGroupIds())
                ->account($request->header->account)
                ->units([null, $this->app->unitId])
                ->apps([null, 'Api', $this->app->appName])
                ->modules([null, self::NAME])
                ->categories([null, PermissionCategory::MEDIA])
                ->permission(PermissionType::READ)
                ->query(MediaMapper::PRIMARYFIELD);

                $collectionMapper->where('', $permWhere);
        }

        /** @var \Modules\Media\Models\Collection $collection */
        $collection = $collectionMapper->execute();

        if ((empty($collection) || $collection->id === 0) && \is_dir(__DIR__ . '/../Files' . $path)) {
            $collection       = new Collection();
            $collection->name = \basename($path);
            $collection->setVirtualPath(\dirname($path));
            $collection->setPath($path);
            $collection->isAbsolute = false;
        }

        if ($collection instanceof Collection && $collection->id > 0) {
            $collectionSources = $collection->getSources();
            foreach ($collectionSources as $source) {
                foreach ($media as $obj) {
                    if ($obj->id === $source->id) {
                        continue 2;
                    }
                }

                $media[] = $source;
            }

            /** @var string[] $glob */
            $glob = $collection->isAbsolute
                ? \glob($collection->getPath() . '/' . $collection->name . '/*')
                : \glob(__DIR__ . '/../Files/' . \trim($collection->getVirtualPath(), '/') . '/' . $collection->name . '/*');
            $glob = $glob === false ? [] : $glob;

            $unIndexedFiles = [];

            foreach ($glob as $file) {
                $basename = \basename($file);
                $realpath = \realpath($file);
                if (($basename[0] === '_' && \strlen($basename) === 5) || $realpath === false) {
                    continue;
                }

                foreach ($media as $obj) {
                    if ($obj->name === $basename
                        || $obj->name . '.' . $obj->extension === $basename
                        || ($obj->getPath() !== '' && StringUtils::endsWith($realpath, $obj->getPath()))
                    ) {
                        continue 2;
                    }
                }

                $pathinfo = \pathinfo($file);

                $localMedia            = new Media();
                $localMedia->name      = $pathinfo['basename'];
                $localMedia->extension = \is_dir($file) ? 'collection' : $pathinfo['extension'] ?? '';
                $localMedia->createdBy = new Account();
                $localMedia->class     = $localMedia->extension === 'collection'
                    ? MediaClass::SYSTEM_DIRECTORY
                    : MediaClass::SYSTEM_FILE;
                $localMedia->setVirtualPath($path);

                $unIndexedFiles[] = $localMedia;
            }

            $media = \array_merge($media, $unIndexedFiles);
        }

        $view->data['media']   = $media;
        $view->data['path']    = $path;
        $view->data['account'] = $this->app->accountManager->get($request->header->account);

        return $view;
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewMediaSingle(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view = new MediaView($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-single');
        $view->data['nav'] = $this->app->moduleManager->get('Navigation')->createNavigationMid(1000401001, $request, $response);

        $id = $request->getDataInt('id');
        if ($id === 0) {
            $path  = \urldecode($request->getDataString('path') ?? '');
            $media = new NullMedia();

            if (\is_file(__DIR__ . '/../Files' . $path)) {
                $name = \explode('.', \basename($path));

                $media->name      = $name[0];
                $media->extension = $name[\count($name) - 1] ?? '';
                $media->setVirtualPath(\dirname($path));
                $media->setPath('/Modules/Media/Files/' . \ltrim($path, '\\/'));
                $media->isAbsolute = false;

                $view->data['view'] = $this->createMediaView($media, $request, $response);
            }
        } else {
            /** @var \Modules\Media\Models\Media $media */
            $media = MediaMapper::get()
                ->with('createdBy')
                ->with('tags')
                ->with('tags/title')
                ->with('content')
                ->where('id', $id)
                ->where('tags/title/language', $request->header->l11n->language)
                ->execute();

            if ($media->class === MediaClass::COLLECTION) {
                /** @var \Modules\Media\Models\Media[] $files */
                $files = MediaMapper::getByVirtualPath(
                    $media->getVirtualPath() . ($media->getVirtualPath() !== '/' ? '/' : '') . $media->name
                )->where('tags/title/language', $request->header->l11n->language)->execute();

                /** @var \Modules\Media\Models\Collection $collection */
                $collection = CollectionMapper::get()->where('id', $id)->execute();
                $media      = \array_merge($files, $collection->getSources());

                $view->data['path'] = $collection->getVirtualPath() . '/' . $collection->name;
                $view->setTemplate('/Modules/Media/Theme/Backend/media-list');
            } else {
                $sub = $request->getDataString('sub') ?? '';
                if (\is_dir($media->getPath() . $sub)) {
                    $listView = new ListView($this->app->l11nManager, $request, $response);
                    $listView->setTemplate('/modules/Media/Theme/Backend/Components/Media/list');
                    $view->data['view'] = $listView;
                } elseif ($media->class === MediaClass::REFERENCE) {
                    /** @var \Modules\Media\Models\Media $media */
                    $media->source = MediaMapper::get()
                        ->with('createdBy')
                        ->with('tags')
                        ->with('tags/title')
                        ->with('content')
                        ->where('id', $media->source?->id ?? 0)
                        ->where('tags/title/language', $request->header->l11n->language)
                        ->execute();

                    $view->data['view'] = $this->createMediaView($media->source, $request, $response);
                } else {
                    $view->data['view'] = $this->createMediaView($media, $request, $response);
                }
            }
        }

        $view->data['account'] = $this->app->accountManager->get($request->header->account);
        $view->data['media']   = $media;

        return $view;
    }

    /**
     * Create media view
     *
     * @param Media $media Media
     *
     * @return View
     *
     * @since 1.0.0
     */
    private function createMediaView(Media $media, RequestAbstract $request, ResponseAbstract $response) : View
    {
        $view = new ElementView($this->app->l11nManager, $request, $response);

        if ($media->hasPassword()
            && !$media->comparePassword((string) $request->getData('password'))
        ) {
            $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/invalidPassword');

            return $view;
        }

        if ($media->isEncrypted) {
            $media = $this->app->moduleManager->get('Media', 'Api')->prepareEncryptedMedia($media, $request);

            if ($media->id === 0) {
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/invalidPassword');

                return $view;
            }
        }

        switch (\strtolower($media->extension)) {
            case 'pdf':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/pdf');
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
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/text');
                break;
            case 'txt':
            case 'cfg':
            case 'log':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/text');
                break;
            case 'md':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/markdown');
                break;
            case 'csv':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/csv');
                break;
            case 'xls':
            case 'xlsx':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/spreadsheet');
                break;
            case 'doc':
            case 'docx':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/word');
                break;
            case 'ppt':
            case 'pptx':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/powerpoint');
                break;
            case 'json':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/json');
                break;
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/image');
                break;
            case 'mp3':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/audio');
                break;
            case 'mp4':
            case 'mpeg':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/video');
                break;
            case 'zip':
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/archive');
                break;
            default:
                $view->setTemplate('/Modules/Media/Theme/Backend/Components/Media/default');
        }

        return $view;
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewModuleSettings(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view              = new View($this->app->l11nManager, $request, $response);
        $view->data['nav'] = $this->app->moduleManager->get('Navigation')->createNavigationMid(1000105001, $request, $response);

        $id = $request->getDataString('id') ?? '';

        $settings               = SettingMapper::getAll()->where('module', $id)->execute();
        $view->data['settings'] = $settings;

        $types               = MediaTypeMapper::getAll()->with('title')->where('title/language', $response->header->l11n->language)->execute();
        $view->data['types'] = $types;

        $view->setTemplate('/Modules/' . static::NAME . '/Admin/Settings/Theme/Backend/settings');

        return $view;
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewMediaTypeSettings(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/' . static::NAME . '/Admin/Settings/Theme/Backend/settings-type');

        /** @var \phpOMS\Localization\BaseStringL11n $type */
        $type = MediaTypeMapper::get()
            ->with('title')
            ->where('title/language', $response->header->l11n->language)
            ->where('id', (int) $request->getData('id'))
            ->execute();

        $view->data['nav']  = $this->app->moduleManager->get('Navigation')->createNavigationMid(1007501001, $request, $response);
        $view->data['type'] = $type;

        $l11n               = MediaTypeL11nMapper::getAll()->where('type', $type->id)->execute();
        $view->data['l11n'] = $l11n;

        return $view;
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewMediaUpload(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-upload');

        return $view;
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewMediaFileCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-file-create');

        $editor               = new \Modules\Editor\Theme\Backend\Components\Editor\BaseView($this->app->l11nManager, $request, $response);
        $view->data['editor'] = $editor;

        return $view;
    }

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return RenderableInterface
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function viewMediaCollectionCreate(RequestAbstract $request, ResponseAbstract $response, mixed $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-collection-create');

        return $view;
    }
}
