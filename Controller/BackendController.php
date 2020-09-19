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

use Modules\Admin\Models\Account;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\NullMedia;
use Modules\Media\Views\MediaView;
use phpOMS\Asset\AssetType;
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
 *
 * @todo Orange-Management/Modules#150
 *  Allow to create new files (not only upload)
 *  In many cases it would be nice to create a new file manually with the module (e.g. create a new .txt or .sqlite file) which then can get edited directly in the media module.
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

    /**
     * Routing end-point for application behaviour.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param mixed            $data     Generic data
     *
     * @return void
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public static function setUpFileUploader(RequestAbstract $request, ResponseAbstract $response, $data = null) : void
    {
        /** @var \phpOMS\Model\Html\Head $head */
        $head = $response->get('Content')->getData('head');
        $head->addAsset(AssetType::JSLATE, 'Modules/Media/Controller.js', ['type' => 'module']);
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
    public function viewMediaList(RequestAbstract $request, ResponseAbstract $response, $data = null) : RenderableInterface
    {
        $view = new View($this->app->l11nManager, $request, $response);
        $view->setTemplate('/Modules/Media/Theme/Backend/media-list');

        $path = (string) ($request->getData('path') ?? '/');

        /** @var Media[] $media */
        $media = MediaMapper::getByVirtualPath(\str_replace('+', ' ', $path));

        $collection = CollectionMapper::getParentCollection(\str_replace('+', ' ', $path));

        if (\is_array($collection) && \is_dir(__DIR__ . '/../Files' . $path)) {
            $collection = new Collection();
            $collection->setName(\basename($path));
            $collection->setVirtualPath(\dirname($path));
            $collection->setPath(\dirname($path));
            $collection->setAbsolute(false);
        }

        if ($collection instanceof Collection) {
            $media += $collection->getSources();

            /** @var string[] $glob */
            $glob = $collection->isAbsolute()
                ? $collection->getPath() . '/' . $collection->getName() . '/*'
                : \glob(__DIR__ . '/../Files/' . \rtrim($collection->getPath(), '/') . '/' . $collection->getName() . '/*');
            $glob = $glob === false ? [] : $glob;

            foreach ($glob as $file) {
                foreach ($media as $obj) {
                    if (($obj->getExtension() !== 'collection'
                            && !empty($obj->getExtension())
                            && $obj->getName() . '.' . $obj->getExtension() === \basename($file))
                        || ($obj->getExtension() === 'collection'
                            && $obj->getName() === \basename($file))
                    ) {
                        continue 2;
                    }
                }

                $pathinfo = \pathinfo($file);

                $localMedia = new Media();
                $localMedia->setName($pathinfo['filename']);
                $localMedia->setExtension(\is_dir($file) ? 'collection' : $pathinfo['extension'] ?? '');
                $localMedia->setVirtualPath($path);
                $localMedia->setCreatedBy(new Account());

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

        $id    = (int) $request->getData('id');
        $media = MediaMapper::get($id);
        if ($media->getExtension() === 'collection') {
            $media = MediaMapper::getByVirtualPath(
                $media->getVirtualPath() . ($media->getVirtualPath() !== '/' ? '/' : '') . $media->getName()
            );

            $collection = CollectionMapper::get((int) $request->getData('id'));
            $media      = \array_merge($media, $collection->getSources());

            $view->addData('path', $collection->getVirtualPath() . '/' . $collection->getName());
            $view->setTemplate('/Modules/Media/Theme/Backend/media-list');
        }

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

        $view->addData('media', $media);

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
