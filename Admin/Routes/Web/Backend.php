<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

use Modules\Media\Controller\BackendController;
use Modules\Media\Models\PermissionCategory;
use phpOMS\Account\PermissionType;
use phpOMS\Router\RouteVerb;

return [
    '^.*/admin/module/settings\?id=Media$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewModuleSettings',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => \Modules\Admin\Models\PermissionCategory::MODULE,
            ],
        ],
    ],
    '^.*/admin/module/settings\?id=Media&type=.*?$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaTypeSettings',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => \Modules\Admin\Models\PermissionCategory::MODULE,
            ],
        ],
    ],
    '^.*/media/list.*$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaList',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/upload.*$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaUpload',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/file/create.*$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaFileCreate',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/collection/create.*$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaCollectionCreate',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/single.*$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaSingle',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
];
