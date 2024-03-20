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
 * Represents the type/class of media in the application.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
use phpOMS\Stdlib\Base\Enum;

/**
 * Media class enum.
 *
 * Used to differentiate the type/class of media
 *
 * @package phpOMS\DataStorage\Database
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
abstract class MediaClass extends Enum
{
    public const FILE = 1;

    public const COLLECTION = 2;

    public const REFERENCE = 3;

    public const SYSTEM_FILE = 4;

    public const SYSTEM_DIRECTORY = 5;
}
