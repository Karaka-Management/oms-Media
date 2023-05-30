<?php
/**
 * Karaka
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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use phpOMS\Autoloader;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

$media = $this->data['media'];

$reader = IOFactory::createReaderforFile(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());

$writer = new Csv($spreadsheet);
$writer->setDelimiter(';');
$writer->setEnclosure('"');
$writer->setLineEnding("\n");
$writer->setUseBOM(true);

$writer->save('php://output');
