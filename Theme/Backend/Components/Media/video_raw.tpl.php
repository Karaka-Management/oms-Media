<?php
/**
 * Karaka
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
<style>html, body, iframe { margin: 0; padding: 0; border: 0; }</style>
<video width="100%" controls>
    <source src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->id); ?>" type="video/<?= $this->media->extension; ?>">
    Your browser does not support HTML video.
</video>
