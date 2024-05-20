<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Template
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use phpOMS\Autoloader;
use phpOMS\Uri\UriFactory;

Autoloader::addPath(__DIR__ . '/../../../../../../Resources/');
?>
<section id="mediaFile" class="portlet col-simple">
    <div class="portlet-body col-simple">
        <div id="media" class="tabview tab-2 m-editor col-simple">
        <ul class="tab-links">
            <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
            <li><label tabindex="0" for="media-c-tab-2">CSV</label>
        </ul>
        <div class="tab-content col-simple">
            <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
            <div class="tab col-simple">
                <iframe class="col-simple" src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->id . '&csrf={$CSRF}'); ?>&type=html"></iframe>
            </div>
            <input type="radio" id="media-c-tab-2" name="tabular-1" checked>
            <div class="tab col-simple">
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
                    <div class="slider">
                    <table class="default sticky">
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
    </div>
</section>