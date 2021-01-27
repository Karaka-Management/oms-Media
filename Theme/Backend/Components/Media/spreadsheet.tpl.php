<?php declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$reader = IOFactory::createReaderforFile(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());

$writer = new Html($spreadsheet);
$writer->writeAllSheets();
?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <?php $writer->save('php://output'); ?>
    </div>
</section>