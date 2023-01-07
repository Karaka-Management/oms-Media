<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use phpOMS\Localization\ISO639x1Enum;
use phpOMS\Localization\BaseStringL11n;

/**
 * Media type class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
class MediaType implements \JsonSerializable
{
    /**
     * Article ID.
     *
     * @var int
     * @since 1.0.0
     */
    protected int $id = 0;

    /**
     * Name.
     *
     * Name used for additional identification, doesn't have to be unique.
     *
     * @var string
     * @since 1.0.0
     */
    public string $name = '';

    /**
     * Is this media type visible in lists or only internal?
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $isVisible = true;

    /**
     * Title.
     *
     * @var string|BaseStringL11n
     * @since 1.0.0
     */
    protected string | BaseStringL11n $title = '';

    /**
     * Constructor.
     *
     * @param string $name Name
     *
     * @since 1.0.0
     */
    public function __construct(string $name = '')
    {
        $this->setL11n($name);
    }

    /**
     * Get id
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getL11n() : string
    {
        return $this->title instanceof BaseStringL11n ? $this->title->content : $this->title;
    }

    /**
     * Set title
     *
     * @param string|BaseStringL11n $title Media article title
     * @param string                $lang  Language
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setL11n(string | BaseStringL11n $title, string $lang = ISO639x1Enum::_EN) : void
    {
        if ($title instanceof BaseStringL11n) {
            $this->title = $title;
        } elseif ($this->title instanceof BaseStringL11n) {
            $this->title->content = $title;
        } else {
            $this->title        = new BaseStringL11n();
            $this->title->ref = $this->id;
            $this->title->content = $title;
            $this->title->setLanguage($lang);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : array
    {
        return [
            'id'     => $this->id,
            'title'  => $this->title,
            'name'   => $this->name,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() : mixed
    {
        return $this->toArray();
    }
}
