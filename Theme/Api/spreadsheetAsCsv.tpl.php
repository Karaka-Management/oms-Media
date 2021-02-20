<?php declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

use PhpOffice\PhpSpreadsheet\Writer\Csv;
use phpOMS\Autoloader;

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
