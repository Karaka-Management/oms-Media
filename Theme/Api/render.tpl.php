<?php
/**
 * Jingga
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

if (\is_file($media->getAbsolutePath())) {
    $fp = \fopen($media->getAbsolutePath(), 'r');
    if ($fp !== false) {
        \fpassthru($fp);
        \fclose($fp);
    }
} elseif (\is_dir($media->getAbsolutePath())) {
    \phpOMS\Utils\IO\Zip\Zip::pack(
        $media->getAbsolutePath(),
        $tmp = \tempnam(\sys_get_temp_dir(), 'oms_tmp_')
    );

    $fp = \fopen($tmp, 'r');
    if ($fp !== false) {
        \fpassthru($fp);
        \fclose($fp);

        \unlink($tmp);
    }
}
