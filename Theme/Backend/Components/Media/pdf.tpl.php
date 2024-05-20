<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use \phpOMS\Uri\UriFactory;

?>

<section id="mediaFile" class="portlet col-simple">
    <div class="portlet-body col-simple">
        <div id="media" class="tabview tab-2 m-editor col-simple">
        <ul class="tab-links">
            <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
            <li><label tabindex="0" for="media-c-tab-2"><?= $this->getHtml('Content', 'Media'); ?></label>
        </ul>
        <div class="tab-content col-simple">
            <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
            <div class="tab col-simple">
                <iframe class="col-simple" id="iHelperFrame" src="<?= UriFactory::build('Resources/mozilla/Pdf/web/viewer.html?file=' . \urlencode(UriFactory::build('{/api}media/export?id=' . $this->media->id . '&csrf={$CSRF}'))); ?>" allowfullscreen></iframe>
            </div>
            <input type="radio" id="media-c-tab-2" name="tabular-1">
            <div class="tab">
                <pre class="textContent" data-tpl-text="/media/content" data-tpl-value="/media/content"><?= $this->printHtml($this->media->content->content); ?></pre>
            </div>
        </div>
    </div>
</section>