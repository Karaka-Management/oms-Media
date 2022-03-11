<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use Modules\Admin\Models\Account;
use Modules\Admin\Models\NullAccount;
use Modules\Tag\Models\NullTag;
use Modules\Tag\Models\Tag;

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 */
class MediaContent implements \JsonSerializable
{
    /**
     * ID.
     *
     * @var int
     * @since 1.0.0
     */
    protected int $id = 0;

    public string $content = '';

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : array
    {
        return [
            'id'             => $this->id,
            'content'           => $this->content,
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
