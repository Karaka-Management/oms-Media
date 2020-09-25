<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package   Modules\HumanResourceManagement\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

use \phpOMS\System\File\ExtensionType;

$fileIconFunction = function (int $extensionType) : string
{
    if ($extensionType === ExtensionType::CODE) {
        return 'file-code';
    } elseif ($extensionType === ExtensionType::TEXT) {
        return 'file-text';
    } elseif ($extensionType === ExtensionType::PRESENTATION) {
       return 'file-powerpoint';
    } elseif ($extensionType === ExtensionType::PDF) {
        return 'file-pdf';
    } elseif ($extensionType === ExtensionType::ARCHIVE) {
        return 'file-zip';
    } elseif ($extensionType === ExtensionType::AUDIO) {
        return 'file-audio';
    } elseif ($extensionType === ExtensionType::VIDEO) {
        return 'file-video';
    } elseif ($extensionType === ExtensionType::IMAGE) {
        return 'file-image-o';
    } elseif ($extensionType === ExtensionType::SPREADSHEET) {
        return 'file-excel';
    } elseif ($extensionType === ExtensionType::DIRECTORY) {
        return 'folder-open';
    }

    return 'file';
};
