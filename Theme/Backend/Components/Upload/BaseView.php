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

namespace Modules\Media\Theme\Backend\Components\Upload;

use phpOMS\Localization\L11nManager;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Views\View;

/**
 * Component view.
 *
 * @package Modules\Media
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 * @codeCoverageIgnore
 */
class BaseView extends View
{
    /**
     * Form id
     *
     * @var string
     * @since 1.0.0
     */
    public string $form = '';

    /**
     * Virtual path of the media file
     *
     * @var string
     * @since 1.0.0
     */
    public string $virtualPath = '';

    /**
     * Name of the image preview
     *
     * @var string
     * @since 1.0.0
     */
    public string $name = '';

    /**
     * Media files
     *
     * @var \Modules\Media\Models\Media[]
     * @since 1.0.0
     */
    public array $files = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(L11nManager $l11n = null, RequestAbstract $request, ResponseAbstract $response)
    {
        parent::__construct($l11n, $request, $response);
        $this->setTemplate('/Modules/Media/Theme/Backend/Components/Upload/upload-list');
    }

    /**
     * {@inheritdoc}
     */
    public function render(mixed ...$data) : string
    {
        /** @var array{0:string, 1?:string, 2?:string} $data */
        $this->form        = $data[0];
        $this->name        = $data[1] ?? 'UNDEFINED';
        $this->virtualPath = $data[2] ?? $this->virtualPath;
        $this->files       = $data[3] ?? $this->files;

        return parent::render();
    }
}
