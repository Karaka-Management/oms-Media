<?php
/**
 * Karaka
 *
 * PHP Version 8.1
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
    private array $files = [];

    /**
     * Get files
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function getFiles() : array
    {
        return $this->files;
    }

    /**
     * Get media file by type
     *
     * @param int $type Media type
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public function getFileByType(int $type) : Media
    {
        foreach ($this->files as $file) {
            if ($file->hasMediaTypeId($type)) {
                return $file;
            }
        }

        return new NullMedia();
    }

    /**
     * Get all media files by type name
     *
     * @param string $type Media type
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public function getFileByTypeName(string $type) : Media
    {
        foreach ($this->files as $file) {
            if ($file->hasMediaTypeName($type)) {
                return $file;
            }
        }

        return new NullMedia();
    }

    /**
     * Get all media files by type name
     *
     * @param string $type Media type
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function getFilesByTypeName(string $type) : array
    {
        $files = [];
        foreach ($this->files as $file) {
            if ($file->hasMediaTypeName($type)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Check if file with a certain type name exists
     *
     * @param string $type Type name
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasFileTypeName(string $type) : bool
    {
        foreach ($this->files as $file) {
            if ($file->hasMediaTypeName($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add media to item
     *
     * @param Media $media Media
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function addFile(Media $media) : void
    {
        $this->files[] = $media;
    }
}
