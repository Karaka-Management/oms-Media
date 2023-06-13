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

use Modules\Media\Models\NullMedia;

/** @var \Modules\Media\Models\Media $media */
$media = $this->media ?? new NullMedia();

$fp = \fopen(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'r');
\fpassthru($fp);
\fclose($fp);
