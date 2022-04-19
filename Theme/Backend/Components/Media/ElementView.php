<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Theme\Backend\Components\Media;

use Modules\Media\Models\Media;
use Modules\Media\Views\MediaView;

/**
 * Component view.
 *
 * @package Modules\Media
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 * @codeCoverageIgnore
 */
class ElementView extends MediaView
{
    /**
     * Media files
     *
     * @var \Modules\Media\Models\Media
     * @since 1.0.0
     */
    protected Media $media;

    /**
     * {@inheritdoc}
     */
    public function render(...$data) : string
    {
        $this->media = $data[0];
        return parent::render();
    }
}
