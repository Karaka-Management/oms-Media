<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use \phpOMS\Uri\UriFactory;

?>

<iframe class="col-simple" id="iHelperFrame" src="<?= UriFactory::build('Resources/mozilla/Pdf/web/viewer.html?file=' . \urlencode(UriFactory::build('{/api}media/export?id=' . $this->media->id))); ?>" allowfullscreen></iframe>