<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

use Modules\Media\Controller\BackendController;
use Modules\Media\Models\PermissionState;
use phpOMS\Account\PermissionType;
use phpOMS\Router\RouteVerb;

return [
    '^.*/media/list.*$' => [
        [
            'dest'       => '\Modules\Media\Controller\BackendController:viewMediaList',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => BackendController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionState::MEDIA,
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
                'state'  => PermissionState::MEDIA,
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
                'state'  => PermissionState::MEDIA,
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
                'state'  => PermissionState::MEDIA,
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
                'state'  => PermissionState::MEDIA,
            ],
        ],
    ],
];
