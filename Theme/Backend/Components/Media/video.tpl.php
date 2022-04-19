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
 * @link      https://karaka.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <video width="100%" controls>
            <source src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->getId()); ?>" type="video/<?= $this->media->extension; ?>">
            Your browser does not support HTML video.
        </video>
    </div>
</section>