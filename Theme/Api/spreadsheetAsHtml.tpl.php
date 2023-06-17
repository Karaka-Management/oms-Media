<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Autoloader;
use phpOMS\Utils\Parser\Spreadsheet\SpreadsheetParser;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

$media = $this->data['media'];

echo SpreadsheetParser::parseSpreadsheet(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'html');
