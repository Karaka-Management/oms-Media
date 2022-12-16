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

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
use phpOMS\Stdlib\Base\Enum;

/**
 * Media status enum.
 *
 * @package phpOMS\DataStorage\Database
 * @license OMS License 1.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
abstract class MediaStatus extends Enum
{
    public const NORMAL = 1;

    public const HIDDEN = 2;

    public const DELETED = 3;
}
