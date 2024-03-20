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

use phpOMS\Utils\StringUtils;

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
class Collection extends Media implements \Iterator
{
    /**
     * Resource id.
     *
     * @var array<int, Media>
     * @since 1.0.0
     */
    public array $sources = [];

    /**
     * Extension name.
     *
     * @var string
     * @since 1.0.0
     */
    public string $extension = 'collection';

    /**
     * Is collection.
     *
     * @var int
     * @since 1.0.0
     */
    public int $class = MediaClass::COLLECTION;

    /**
     * Set sources.
     *
     * @param array $sources Source array
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setSources(array $sources) : void
    {
        $this->sources = $sources;
    }

    /**
     * Set sources.
     *
     * @param Media $source Source
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function addSource(Media $source) : void
    {
        $this->sources[] = $source;
    }

    /**
     * Get sources.
     *
     * @return Media[]
     *
     * @since 1.0.0
     */
    public function getSources() : array
    {
        return $this->sources;
    }

    /**
     * Get media element by its name.
     *
     * @param string $name Name of the media element
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public function getSourceByName(string $name) : Media
    {
        foreach ($this->sources as $source) {
            if ($source->name === $name) {
                return $source;
            }
        }

        return new NullMedia();
    }

    /**
     * Find file by file name
     *
     * @param string $name File name
     *
     * @return Media
     *
     * @since 1.0.0
     */
    public function findFile(string $name) : Media
    {
        foreach ($this->sources as $file) {
            if (StringUtils::endsWith($file->getPath(), $name)) {
                return $file;
            }
        }

        return new NullMedia();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind() : void
    {
        \reset($this->sources);
    }

    /**
     * {@inheritdoc}
     */
    public function current() : Media
    {
        $current = \current($this->sources);

        return $current === false ? $this : $current;
    }

    /**
     * {@inheritdoc}
     */
    public function key() : ?int
    {
        return \key($this->sources);
    }

    /**
     * {@inheritdoc}
     */
    public function next() : void
    {
        \next($this->sources);
    }

    /**
     * {@inheritdoc}
     */
    public function valid() : bool
    {
        return \current($this->sources) !== false;
    }
}
