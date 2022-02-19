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

$media = $this->getData('media');

$fp = \fopen(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'r');
\fpassthru($fp);
\fclose($fp);
