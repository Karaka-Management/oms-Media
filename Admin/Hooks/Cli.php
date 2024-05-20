<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

return [
    'Module:Admin-encryption-change' => [
        'callback' => ['\Modules\Media\Controller\CliController:runEncryptionChangeFromHook'],
    ],
];
