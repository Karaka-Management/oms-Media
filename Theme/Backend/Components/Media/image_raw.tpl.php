<?php
/**
 * Jingga
 *
 * PHP Version 8.2
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
<!DOCTYPE html>
<style>html, body, iframe { margin: 0; padding: 0; border: 0; }</style>
<img alt="<?= $this->printHtml($this->media->name); ?>" style="max-width: 100%" src="<?= $this->media->id !== 0
        ? UriFactory::build('{/api}media/export?id=' . $this->media->id . '&csrf={$CSRF}')
        : UriFactory::build('{/api}media/export?path=' . \urlencode($this->media->getPath()) . '&csrf={$CSRF}');
    ?>">
