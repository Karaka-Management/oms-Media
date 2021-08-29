<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

// @todo: is this chunked/streamed output or bulk output
// if it is streamed it is not working because of ob_* in the actual response rendering

$media = $this->getData('media');

$fp = \fopen(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'r');
\fpassthru($fp);
\fclose($fp);
