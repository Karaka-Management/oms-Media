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

use Modules\Media\Controller\SearchController;
use Modules\Media\Models\PermissionCategory;
use phpOMS\Account\PermissionType;
use phpOMS\Router\RouteVerb;

return [
    '^(?!:).+.*?' => [
        [
            'dest'       => '\Modules\Media\Controller\SearchController:searchGeneral',
            'verb'       => RouteVerb::ANY,
            'active'     => true,
            'order'      => 3,
            'permission' => [
                'module' => SearchController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^:tag (\?.*$|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\SearchController:searchTag',
            'verb'       => RouteVerb::ANY,
            'active'     => true,
            'permission' => [
                'module' => SearchController::NAME,
                'type'   => PermissionType::READ,
            ],
        ],
    ],
];
