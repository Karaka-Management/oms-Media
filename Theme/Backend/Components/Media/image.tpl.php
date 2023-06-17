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
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div class="h-overflow centerText">
            <img alt="<?= $this->printHtml($this->media->name); ?>" style="max-width: 100%" src="<?= $this->media->id !== 0
                    ? UriFactory::build('{/api}media/export?id=' . $this->media->id)
                    : UriFactory::build('{/api}media/export?path=' . \urlencode($this->media->getPath()));
                ?>">
        </div>
    </div>
</section>