<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use phpOMS\Autoloader;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

$media = $this->getData('media');

$reader = IOFactory::createReaderforFile(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());

$writer = new Csv($spreadsheet);
$writer->setDelimiter(';');
$writer->setEnclosure('"');
$writer->setLineEnding("\n");
$writer->setUseBOM(true);

$writer->save('php://output');
