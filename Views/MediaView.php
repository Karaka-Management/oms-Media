<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media\Views
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Views;

use Modules\Media\Models\Media;
use phpOMS\System\File\ExtensionType;
use phpOMS\System\File\FileUtils;
use phpOMS\System\File\Local\File;
use phpOMS\Utils\StringUtils;
use phpOMS\Views\View;

/**
 * Media view.
 *
 * @package Modules\Media\Views
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
class MediaView extends View
{
    /**
     * Get file path
     *
     * @param Media  $media Media file
     * @param string $sub   Sub path
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function filePathFunction(Media $media, string $sub) : string
    {
        if (\is_file($media->getPath() . $sub)
            && ($path = \realpath($media->getPath() . $sub)) !== false
            && ($path = \strtr($path, '\\', '/')) !== false
            && StringUtils::startsWith($path, $media->getPath())
        ) {
            return $media->getPath() . $sub;
        }

        return $media->getPath();
    }

    /**
     * Get directory path
     *
     * @param Media  $media Media file
     * @param string $sub   Sub path
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function dirPathFunction(Media $media, string $sub) : string
    {
        if (\is_dir($media->getPath() . $sub)
            && ($path = \realpath($media->getPath() . $sub)) !== false
            && ($path = \strtr($path, '\\', '/')) !== false
            && StringUtils::startsWith($path, $media->getPath())
        ) {
            return $media->getPath() . $sub;
        }

        return $media->getPath();
    }

    /**
     * Check if media file is a collection
     *
     * @param Media  $media Media file
     * @param string $sub   Sub path
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function isCollectionFunction(Media $media, ?string $sub = null) : bool
    {
        return ($media->extension === 'collection'
                && !\is_file($media->getPath() . ($sub ?? '')))
            || (\is_dir($media->getPath())
                && ($sub === null || \is_dir($media->getPath() . $sub))
        );
    }

    /**
     * Get file content
     *
     * @param string $path File path
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function getFileContent(string $path) : string
    {
        if (!\is_file($path)) {
            return '';
        }

        $output = \file_get_contents($path);
        if ($output === false) {
            return ''; // @codeCoverageIgnore
        }

        return \str_replace(["\r\n", "\r"], "\n", $output);
    }

    /**
     * Get file content
     *
     * @param string $path File path
     *
     * @return array
     *
     * @since 1.0.0
     */
    protected function lineContentFunction(string $path) : array
    {
        if (!\is_file($path)) {
            return [];
        }

        $output = \file_get_contents($path);
        if ($output === false) {
            return []; // @codeCoverageIgnore
        }

        $output = \str_replace(["\r\n", "\r"], "\n", $output);

        return \explode("\n", $output);
    }

    /**
     * Check if media file is image file
     *
     * @param Media  $media Media file
     * @param string $path  File path
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function isImageFile(Media $media, string $path = '') : bool
    {
        return FileUtils::getExtensionType($media->extension) === ExtensionType::IMAGE
            || FileUtils::getExtensionType(File::extension($path)) === ExtensionType::IMAGE;
    }

    /**
     * Check if media file is video file
     *
     * @param Media  $media Media file
     * @param string $path  File path
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function isVideoFile(Media $media, string $path = '') : bool
    {
        return FileUtils::getExtensionType($media->extension) === ExtensionType::VIDEO
            || FileUtils::getExtensionType(File::extension($path)) === ExtensionType::VIDEO;
    }

    /**
     * Check if media file is audio file
     *
     * @param Media  $media Media file
     * @param string $path  File path
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function isAudioFile(Media $media, string $path = '') : bool
    {
        return FileUtils::getExtensionType($media->extension) === ExtensionType::AUDIO
            || FileUtils::getExtensionType(File::extension($path)) === ExtensionType::AUDIO;
    }

    /**
     * Check if media file is text file
     *
     * @param Media  $media Media file
     * @param string $path  File path
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function isTextFile(Media $media, string $path = '') : bool
    {
        $mediaExtension = FileUtils::getExtensionType($media->extension);
        $pathExtension  = FileUtils::getExtensionType(File::extension($path));

        return $mediaExtension === ExtensionType::TEXT || $pathExtension === ExtensionType::TEXT
            || $mediaExtension === ExtensionType::CODE || $pathExtension === ExtensionType::CODE;
    }
}
