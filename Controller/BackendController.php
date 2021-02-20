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

use Modules\Admin\Models\Account;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\NullMedia;
use Modules\Media\Theme\Backend\Components\Media\ElementView;
use Modules\Media\Theme\Backend\Components\Media\ListView;
use Modules\Media\Views\MediaView;
use phpOMS\Contract\RenderableInterface;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Views\View;

/**
 * Media class.
 *
 * @package Modules\Media
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class BackendController extends Controller
{
    /**
     * Module path.
     *
     * @var string
     * @since 1.0.0
     */
    public const MODULE_PATH = __DIR__;

    /**
     * Module version.
     *
     * @var string
     * @since 1.0.0
     */
    public const MODULE_VERSION = '1.0.0';

    /**
     * Module name.
     *
     * @var string
     * @since 1.0.0
     */
    public const MODULE_NAME = 'Media';

    /**
     * Module id.
     *
     * @var int
     * @since 1.0.0
     */
    public const MODULE_ID = 1000400000;

    /**
     * Providing.
     *
     * @var string[]
     * @since 1.0.0
     */
    protected static array $providing = [];

    /**
     * Dependencies.
     *
     * @var string[]
     * @since 1.0.0
     */
    protected static array $dependencies = [];

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
    public function viewMediaList(RequestAbstract $request, ResponseAbstract $response, $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-list');

        $path = \str_replace('+', ' ', (string) ($request->getData('path') ?? '/'));

        /** @var Media[] $media */
        $media      = MediaMapper::getByVirtualPath($path);
        $collection = CollectionMapper::getParentCollection($path);

        if (\is_array($collection) && \is_dir(__DIR__ . '/../Files' . $path)) {
            $collection       = new Collection();
            $collection->name = \basename($path);
            $collection->setVirtualPath(\dirname($path));
            $collection->setPath($path);
            $collection->isAbsolute = false;
        }

        if ($collection instanceof Collection) {
            $media += $collection->getSources();

            /** @var string[] $glob */
            $glob = $collection->isAbsolute
                ? $collection->getPath() . '/' . $collection->name . '/*'
                : \glob(__DIR__ . '/../Files/' . \trim($collection->getVirtualPath(), '/') . '/' . $collection->name . '/*');
            $glob = $glob === false ? [] : $glob;

            foreach ($glob as $file) {
                $basename = \basename($file);
                if ($basename[0] === '_' && \strlen($basename) === 3) {
                    continue;
                }

                foreach ($media as $obj) {
                    if ($obj->name === $basename
                        || $obj->name . '.' . $obj->extension === $basename
                    ) {
                        continue 2;
                    }
                }

                $pathinfo = \pathinfo($file);

                $localMedia            = new Media();
                $localMedia->name      = $pathinfo['filename'];
                $localMedia->extension = \is_dir($file) ? 'collection' : $pathinfo['extension'] ?? '';
                $localMedia->setVirtualPath($path);
                $localMedia->createdBy = new Account();

                $media[] = $localMedia;
            }
        }

        $view->addData('media', $media);
        $view->addData('path', $path);

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
    public function viewMediaSingle(RequestAbstract $request, ResponseAbstract $response, $data = null) : RenderableInterface
    {
        $view = new MediaView($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-single');
        $view->addData('nav', $this->app->moduleManager->get('Navigation')->createNavigationMid(1000401001, $request, $response));

        $id = $request->getData('id', 'int');
        if ($id === 0) {
            $path  = \urldecode($request->getData('path'));
            $media = new NullMedia();
            if (\is_file(__DIR__ . '/../Files' . $path)) {
                $name = \explode('.', \basename($path));

                $media->name      = $name[0];
                $media->extension = $name[1] ?? '';
                $media->setVirtualPath(\dirname($path));
                $media->setPath('/Modules/Media/Files/' . \ltrim($path, '\\/'));
                $media->isAbsolute = false;

                $view->addData('view', $this->createMediaView($media, $request, $response));
            }
        } else {
            $media = MediaMapper::get($id);
            if ($media->extension === 'collection') {
                $media = MediaMapper::getByVirtualPath(
                    $media->getVirtualPath() . ($media->getVirtualPath() !== '/' ? '/' : '') . $media->name
                );

                $collection = CollectionMapper::get($id);
                $media      = \array_merge($media, $collection->getSources());

                $view->addData('path', $collection->getVirtualPath() . '/' . $collection->name);
                $view->setTemplate('/Modules/Media/Theme/Backend/media-list');
            } else {
                $sub = $request->getData('sub') ?? '';
                if (\is_dir($media->getPath())
                    && (\is_dir($media->getPath() . $sub))
                ) {
                    $listView = new ListView($this->app->l11nManager, $request, $response);
                    $listView->setTemplate('/modules/Media/Theme/Backend/Components/Media/list');
                    $view->addData('view', $listView);
                } else {
                    $view->addData('view', $this->createMediaView($media, $request, $response));
                }
            }
        }

        $view->addData('media', $media);

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
    public function viewMediaUpload(RequestAbstract $request, ResponseAbstract $response, $data = null) : RenderableInterface
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
    public function viewMediaFileCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-file-create');

        $editor = new \Modules\Editor\Theme\Backend\Components\Editor\BaseView($this->app->l11nManager, $request, $response);
        $view->addData('editor', $editor);

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
    public function viewMediaCollectionCreate(RequestAbstract $request, ResponseAbstract $response, $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-collection-create');

        return $view;
    }
}
