<?php
/**
 * Karaka
 *
 * PHP Version 8.0
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
        <iframe style="min-height: 600px;" data-form="iUiSettings" data-name="iframeHelper" id="iHelperFrame" src="<?= UriFactory::build('{/backend}Resources/mozilla/Pdf/web/viewer.html?{?}&file=' . ($this->media->isAbsolute ? '' : '/../../../../') . $this->media->getPath()); ?>" allowfullscreen></iframe>
    </div>
</section>