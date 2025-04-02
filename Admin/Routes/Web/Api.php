<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
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
            'csrf'       => true,
            'active'     => true,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
        [
            'dest'       => '\Modules\Media\Controller\ApiController:apiMediaUpdate',
            'verb'       => RouteVerb::SET,
            'csrf'       => true,
            'active'     => true,
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
            'csrf'       => true,
            'active'     => true,
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
            'csrf'       => true,
            'active'     => true,
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
            'csrf'       => true,
            'active'     => true,
            'permission' => [
                'module' => ApiController::NAME,
                'type'   => PermissionType::CREATE,
                'state'  => PermissionCategory::MEDIA,
            ],
        ],
    ],
];
