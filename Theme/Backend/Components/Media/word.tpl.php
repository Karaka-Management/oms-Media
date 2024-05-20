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

use phpOMS\Uri\UriFactory;

?>
<section id="mediaFile" class="portlet col-simple">
    <div class="portlet-body col-simple">
        <iframe class="col-simple" src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->id . '&csrf={$CSRF}'); ?>&type=html"></iframe>
    </div>
</section>
