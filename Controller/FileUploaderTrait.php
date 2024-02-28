<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media\Controller
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Controller;

use phpOMS\Asset\AssetType;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;

/**
 * Trait for setting up file upload functionality.
 *
 * @package Modules\Media\Controller
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
trait FileUploaderTrait
{
    /**
     * Setup file uploader.
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Misc. data
     *
     * @return void
     *
     * @since 1.0.0
     * @codeCoverageIgnore
     */
    public function setUpFileUploaderTrait(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        $head  = $response->data['Content']->head;
        $nonce = $this->app->appSettings->getOption('script-nonce');

        $head->addAsset(AssetType::JS, '/Modules/Media/Controller.js?v=' . self::VERSION, ['nonce' => $nonce, 'type' => 'module']);
    }
}
