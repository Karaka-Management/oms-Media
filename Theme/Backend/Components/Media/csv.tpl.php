<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Template
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

use phpOMS\Utils\IO\Csv\CsvSettings;

?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div id="media" class="tabview tab-2 m-editor">
        <ul class="tab-links">
            <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
            <li><label tabindex="0" for="media-c-tab-2"><?= $this->getHtml('Edit', 'Media'); ?></label>
        </ul>
        <div class="tab-content">
            <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
            <div class="tab">
                <table class="default">
                <?php
                    $f = \fopen(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath(), 'r');

                    $delim = CsvSettings::getFileDelimiter($f, 3);
                    while (($line = \fgetcsv($f, 0, $delim)) !== false) {
                        echo '<tr>';
                        foreach ($line as $cell) {
                            echo '<td>' . \htmlspecialchars($cell);
                        }
                    }

                    \fclose($f);
                ?>
                </table>
            </div>
            <input type="radio" id="media-c-tab-2" name="tabular-1">
            <div class="tab">
                <pre class="textContent" data-tpl-text="/media/content" data-tpl-value="/media/content"><?= $this->printHtml(
                    $this->getFileContent(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath())
                ); ?></pre>
            </div>
        </div>
    </div>
</section>