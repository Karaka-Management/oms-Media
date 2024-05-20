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

use phpOMS\Utils\Parser\Markdown\Markdown;

?>
<!DOCTYPE html>
<style>html, body, iframe { margin: 0; padding: 0; border: 0; }</style>
<article><?= Markdown::parse(
    $this->getFileContent(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath())
); ?></article>
