<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML;
use phpOMS\Autoloader;

Autoloader::addPath(__DIR__ . '/../../../../Resources/');

$media = $this->media;

$reader = IOFactory::createReader('Word2007');
$doc    = $reader->load(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath());

$writer = new HTML($doc);
$writer->save('php://output');
