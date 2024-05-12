<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 *
 * @property \Modules\Media\Models\Media[] $files
 */
trait MediaListTrait
{
    /**
     * Files.
     *
     * @var Media[]
     * @since 1.0.0
     */
    public array $files = [];

    /**
     * Get media file by tag
     *
     * @param int $tag Media tag
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public function getFileByTag(int $tag) : Media
    {
        foreach ($this->files as $file) {
            if ($file->hasMediaTagId($tag)) {
                return $file;
            }
        }

        return new NullMedia();
    }

    /**
     * Get all media files by tag name
     *
     * @param string $tag Media tag
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public function getFileByTagName(string $tag) : Media
    {
        foreach ($this->files as $file) {
            if ($file->hasMediaTagName($tag)) {
                return $file;
            }
        }

        return new NullMedia();
    }

    /**
     * Get all media files by tag name
     *
     * @param string $tag Media tag
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function getFilesByTagName(string $tag) : array
    {
        $files = [];
        foreach ($this->files as $file) {
            if ($file->hasMediaTagName($tag)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Check if file with a certain tag name exists
     *
     * @param string $tag Tag name
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasFileTagName(string $tag) : bool
    {
        foreach ($this->files as $file) {
            if ($file->hasMediaTagName($tag)) {
                return true;
            }
        }

        return false;
    }
}
