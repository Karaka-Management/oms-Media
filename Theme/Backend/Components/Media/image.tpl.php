<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   Template
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>
<section id="mediaFile" class="portlet col-simple">
    <div class="portlet-body col-simple">
        <div id="media" class="tabview tab-2 m-editor col-simple">
        <ul class="tab-links">
            <li><label tabindex="0" for="media-c-tab-1"><?= $this->getHtml('Preview', 'Media'); ?></label>
            <?php if (!empty($this->media->content->content)) : ?>
            <li><label tabindex="0" for="media-c-tab-2"><?= $this->getHtml('Content', 'Media'); ?></label>
            <?php endif; ?>
        </ul>
        <div class="tab-content col-simple">
            <input type="radio" id="media-c-tab-1" name="tabular-1" checked>
            <div class="x-overflow cT">
                <img alt="<?= $this->printHtml($this->media->name); ?>" style="max-width: 100%; max-height: 100%; align-self: center;" src="<?= $this->media->id !== 0
                        ? UriFactory::build('{/api}media/export?id=' . $this->media->id)
                        : UriFactory::build('{/api}media/export?path=' . \urlencode($this->media->getPath()));
                    ?>">
            </div>
            <?php if (!empty($this->media->content->content)) : ?>
            <input type="radio" id="media-c-tab-2" name="tabular-1">
            <div class="tab">
                <pre class="textContent" data-tpl-text="/media/content" data-tpl-value="/media/content"><?= $this->printHtml($this->media->content->content); ?></pre>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>