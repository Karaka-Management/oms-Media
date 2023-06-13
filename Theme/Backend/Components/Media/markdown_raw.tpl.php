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

use phpOMS\Utils\Parser\Markdown\Markdown;

?>

<article><?= Markdown::parse(
    $this->getFileContent(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath())
); ?></article>
