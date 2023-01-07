<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Template
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Autoloader;
use phpOMS\Uri\UriFactory;
use phpOMS\Utils\Parser\Presentation\PresentationParser;

Autoloader::addPath(__DIR__ . '/../../../../../../Resources/');
?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div id="media" class="tabview tab-2 m-editor">
            <ul class="tab-links">
                <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
                <li><label tabindex="0" for="media-c-tab-2">Status</label>
            </ul>
            <div class="tab-content">
                <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
                <div class="tab">
                    <iframe src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->getId()); ?>&type=html"></iframe>
                </div>
                <input type="radio" id="media-c-tab-2" name="tabular-1" checked>
                <div class="tab">
                    <?php
                        echo PresentationParser::parsePresentation($this->media->getPath(), 'html');
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>