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

use phpOMS\Autoloader;
use phpOMS\Utils\Parser\Spreadsheet\SpreadsheetParser;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

$media = $this->media;

echo SpreadsheetParser::parseSpreadsheet(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'html');
