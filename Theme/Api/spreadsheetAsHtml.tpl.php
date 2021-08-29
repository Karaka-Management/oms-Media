<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use phpOMS\Autoloader;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

$media = $this->getData('media');

$reader = IOFactory::createReaderforFile(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());

$writer = new Html($spreadsheet);
$writer->writeAllSheets();

$writer->save('php://output');

echo '<style>body { margin: 0; } table { width: 100%; } table .n, table .s { text-align: left; } td { padding: .5rem; }</style>';
