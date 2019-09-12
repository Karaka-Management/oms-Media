<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package    TBD
 * @copyright  Dennis Eichhorn
 * @license    OMS License 1.0
 * @version    1.0.0
 * @link       https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\Theme\Backend\Components\InlinePreview;

use phpOMS\ApplicationAbstract;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\Views\View;

/**
 * Component view.
 *
 * @package    TBD
 * @license    OMS License 1.0
 * @link       https://orange-management.org
 * @since      1.0.0
 * @codeCoverageIgnore
 */
class BaseView extends View
{
    protected $form = '';

    protected $virtualPath = '';

    protected $name = '';

    /**
     * {@inheritdoc}
     */
    public function __construct(ApplicationAbstract $app, RequestAbstract $request, ResponseAbstract $response)
    {
        parent::__construct($app, $request, $response);
        $this->setTemplate('/Modules/Media/Theme/Backend/Components/InlinePreview/inline-preview');
    }

    /**
     * {@inheritdoc}
     */
    public function render(...$data) : string
    {
        $this->form        = $data[0];
        $this->name        = $data[1];
        $this->virtualPath = $data[2] ?? '/';
        return parent::render();
    }
}