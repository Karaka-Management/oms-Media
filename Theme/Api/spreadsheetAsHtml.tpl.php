<?php declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

use PhpOffice\PhpSpreadsheet\Writer\Html;
use phpOMS\Autoloader;

$media = $this->getData('media');

$reader = IOFactory::createReaderforFile(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());

$writer = new Html($spreadsheet);
$writer->writeAllSheets();

$writer->save('php://output');

echo '<style>body { margin: 0; } table { width: 100%; }</style>';
