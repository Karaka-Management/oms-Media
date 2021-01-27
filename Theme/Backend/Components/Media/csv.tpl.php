<?php

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
                <?php
                    $f = \fopen(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath(), 'r');

                    echo '<table class="default">';
                    $delim = CsvSettings::getFileDelimiter($f, 3);
                    while (($line = \fgetcsv($f, 0, $delim)) !== false) {
                        echo '<tr>';
                        foreach ($line as $cell) {
                            echo '<td>' . \htmlspecialchars($cell);
                        }
                    }

                    \fclose($f);
                    echo '</table>';
                ?>
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