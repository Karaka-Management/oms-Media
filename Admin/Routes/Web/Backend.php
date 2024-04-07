<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use Modules\Media\Controller\BackendController;
use Modules\Media\Models\PermissionCategory;
use phpOMS\Account\PermissionType;
use phpOMS\Router\RouteVerb;

return [
    '^/admin/module/settings\?id=Media&type=.*?$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaTypeSettings',
            'verb'       => RouteVerb::GET,
            'active' => true,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => \Modules\Admin\Models\PermissionCategory::MODULE,
            ],
        ],
    ],
    '^/media/list(\?.*$|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaList',
            'verb'       => RouteVerb::GET,
            'active' => true,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^/media/upload(\?.*$|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaUpload',
            'verb'       => RouteVerb::GET,
            'active' => true,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^/media/file/create(\?.*$|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaFileCreate',
            'verb'       => RouteVerb::GET,
            'active' => true,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^/media/collection/create(\?.*$|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaCollectionCreate',
            'verb'       => RouteVerb::GET,
            'active' => true,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^/media/view(\?.*$|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaView',
            'verb'       => RouteVerb::GET,
            'active' => true,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
];
