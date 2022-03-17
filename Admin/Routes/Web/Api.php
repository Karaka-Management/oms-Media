<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Modules
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

use Modules\Media\Controller\ApiController;
use Modules\Media\Models\PermissionCategory;
use phpOMS\Account\PermissionType;
use phpOMS\Router\RouteVerb;

return [
    '^.*/media(\?+.*|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\ApiController:apiMediaUpload',
            'verb'       => RouteVerb::PUT,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
        [
            'dest'       => '\Modules\Media\Controller\ApiController:apiMediaUpdate',
            'verb'       => RouteVerb::SET,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/file(\?+.*|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\ApiController:apiMediaCreate',
            'verb'       => RouteVerb::PUT,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/export(\?+.*|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\ApiController:apiMediaExport',
            'verb'       => RouteVerb::GET,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::READ,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
    '^.*/media/collection(\?+.*|$)' => [
        [
            'dest'       => '\Modules\Media\Controller\ApiController:apiCollectionCreate',
            'verb'       => RouteVerb::PUT,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
];
