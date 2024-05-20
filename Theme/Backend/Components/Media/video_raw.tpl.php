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
<!DOCTYPE html>
<style>html, body, iframe { margin: 0; padding: 0; border: 0; }</style>
<video width="100%" controls>
    <source src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->id . '&csrf={$CSRF}'); ?>" type="video/<?= $this->media->extension; ?>">
    Your browser does not support HTML video.
</video>
