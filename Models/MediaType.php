<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use phpOMS\Contract\ArrayableInterface;
use phpOMS\Localization\ISO639x1Enum;

/**
 * Media type class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
class MediaType implements \JsonSerializable, ArrayableInterface
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
     * @var string
     * @since 1.0.0
     */
    protected string $name = '';

    /**
     * Title.
     *
     * @var string|MediaTypeL11n
     * @since 1.0.0
     */
    protected $title = '';

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
        return $this->title instanceof MediaTypeL11n ? $this->title->title : $this->title;
    }

    /**
     * Set title
     *
     * @param string|MediaTypeL11n $title Media article title
     * @param string               $lang  Language
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setL11n(string | MediaTypeL11n $title, string $lang = ISO639x1Enum::_EN) : void
    {
        if ($title instanceof MediaTypeL11n) {
            $this->title = $title;
        } elseif (isset($this->title) && $this->title instanceof MediaTypeL11n) {
            $this->title->title = $title;
        } else {
            $this->title        = new MediaTypeL11n();
            $this->title->title = $title;
            $this->title->setLanguage($lang);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : array
    {
        return [
            'id'    => $this->id,
            'title' => $this->title,
            'name'  => $this->name,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
