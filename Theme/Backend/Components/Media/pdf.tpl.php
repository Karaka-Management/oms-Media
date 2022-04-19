<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

use \phpOMS\Uri\UriFactory;

?>

<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div id="media" class="tabview tab-2 m-editor">
        <ul class="tab-links">
            <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
            <li><label tabindex="0" for="media-c-tab-2"><?= $this->getHtml('Content', 'Media'); ?></label>
        </ul>
        <div class="tab-content">
            <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
            <div class="tab">
                <iframe style="min-height: 600px;" data-form="iUiSettings" data-name="iframeHelper" id="iHelperFrame" src="<?= UriFactory::build('{/backend}Resources/mozilla/Pdf/web/viewer.html?{?}&file=' . \urlencode(($this->media->isAbsolute ? '' : '/../../../../') . $this->media->getPath())); ?>" allowfullscreen></iframe>
            </div>
            <input type="radio" id="media-c-tab-2" name="tabular-1">
            <div class="tab">
                <pre class="textContent" data-tpl-text="/media/content" data-tpl-value="/media/content"><?= $this->printHtml($this->media->content->content); ?></pre>
            </div>
        </div>
    </div>
</section>