<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Template
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use phpOMS\Autoloader;
use phpOMS\Uri\UriFactory;

Autoloader::addPath(__DIR__ . '/../../../../../../Resources/');
?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div id="media" class="tabview tab-2 m-editor">
        <ul class="tab-links">
            <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
            <li><label tabindex="0" for="media-c-tab-2">CSV</label>
        </ul>
        <div class="tab-content">
            <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
            <div class="tab">
                <iframe src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->getId()); ?>&type=html"></iframe>
            </div>
            <input type="radio" id="media-c-tab-2" name="tabular-1" checked>
            <div class="tab">
                <?php
                    $reader = IOFactory::createReaderforFile(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());

                    $writer = new Csv($spreadsheet);
                    $writer->setDelimiter(';');
                    $writer->setEnclosure('"');
                    $writer->setLineEnding("\n");
                    $writer->setUseBOM(true);

                    \ob_start();
                    $writer->save('php://output');
                    $data = \ob_get_clean();
                    $csv  = \explode("\n", \trim($data, "\n"));
                    ?>
                    <table class="default">
                    <?php
                    foreach ($csv as $line)  {
                        $lineCsv = \str_getcsv($line, ';', '"');
                        if ($lineCsv === null) {
                            break;
                        }

                        echo '<tr>';
                        foreach ($lineCsv as $cell) {
                            if ($cell === null) {
                                break;
                            }

                            echo '<td>' . \htmlspecialchars($cell);
                        }
                    }
                    ?>
                    </table>
            </div>
        </div>
    </div>
</section>