<?php declare(strict_types=1);

use PhpOffice\PhpPresentation\IOFactory;

Autoloader::addPath(__DIR__ . '/../../../../../../Resources/');

use PhpOffice\PhpPresentation\Writer\Html;
use phpOMS\Autoloader;

$reader       = IOFactory::createReader('PowerPoint2007');
$presentation = $reader->load(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());

$writer = new Html($presentation);
?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <?php $writer->save('php://output'); ?>
    </div>
</section>
