<?php
/**
 * Jingga
 *
 * PHP Version 8.2
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
<!DOCTYPE html>
<style>html, body, iframe { margin: 0; padding: 0; border: 0; width: 100%; height: 100%; overflow: hidden; }</style>
<iframe
    class="col-simple"
    id="iMediaFrame"
    width="100%"
    height="100%"
    src="<?= UriFactory::build(
        '/Resources/mozilla/Pdf/web/viewer.html?file='
        . ($this->media->id === 0
            ? \urlencode(UriFactory::build('{/api}media/export?path=' . $this->media->getPath()))
            : \urlencode(UriFactory::build('{/api}media/export?id=' . $this->media->id)
        )));
    ?>" allowfullscreen></iframe>