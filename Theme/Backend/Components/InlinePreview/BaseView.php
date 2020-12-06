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

namespace Modules\Media\Theme\Backend\Components\InlinePreview;

use phpOMS\Localization\L11nManager;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Views\View;

/**
 * Component view.
 *
 * @package Modules\Media
 * @license OMS License 1.0
 * @link    https://orange-management.org
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
    protected string $id = '';

    /**
     * Form id
     *
     * @var string
     * @since 1.0.0
     */
    protected string $form = '';

    /**
     * Virtual path of the media file
     *
     * @var string
     * @since 1.0.0
     */
    protected string $virtualPath = '';

    /**
     * Name of the image preview
     *
     * @var string
     * @since 1.0.0
     */
    protected string $name = '';

    /**
     * Is required?
     *
     * @var bool
     * @since 1.0.0
     */
    private bool $isRequired = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(L11nManager $l11n = null, RequestAbstract $request, ResponseAbstract $response)
    {
        parent::__construct($l11n, $request, $response);
        $this->setTemplate('/Modules/Media/Theme/Backend/Components/InlinePreview/inline-preview');
    }

    /**
     * {@inheritdoc}
     */
    public function render(...$data) : string
    {
        $this->form        = $data[0];
        $this->id          = $data[1];
        $this->name        = $data[2];
        $this->virtualPath = $data[3] ?? '/';
        $this->isRequired  = $data[4] ?? false;
        return parent::render();
    }

    /**
     * Get selector id
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Is required?
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isRequired() : bool
    {
        return $this->isRequired;
    }
}
