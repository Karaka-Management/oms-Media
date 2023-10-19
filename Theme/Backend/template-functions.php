<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   Modules\HumanResourceManagement\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use \phpOMS\System\File\ExtensionType;

$fileIconFunction = function (int $extensionType) : string
{
    if ($extensionType === ExtensionType::CODE) {
        return 'code_blocks';
    } elseif ($extensionType === ExtensionType::TEXT || $extensionType === ExtensionType::WORD) {
        return 'description';
    } elseif ($extensionType === ExtensionType::PRESENTATION) {
        return 'present_to_all';
    } elseif ($extensionType === ExtensionType::PDF) {
        return 'picture_as_pdf';
    } elseif ($extensionType === ExtensionType::ARCHIVE) {
        return 'folder_zip';
    } elseif ($extensionType === ExtensionType::AUDIO) {
        return 'music_note';
    } elseif ($extensionType === ExtensionType::VIDEO) {
        return 'video_library';
    } elseif ($extensionType === ExtensionType::IMAGE) {
        return 'image';
    } elseif ($extensionType === ExtensionType::SPREADSHEET) {
        return 'table';
    } elseif ($extensionType === ExtensionType::DIRECTORY) {
        return 'folder_open';
    } elseif ($extensionType === ExtensionType::REFERENCE) {
        return 'switch_access_shortcut';
    }

    return 'description';
};
