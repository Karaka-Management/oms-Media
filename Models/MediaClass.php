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

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 */
use phpOMS\Stdlib\Base\Enum;

/**
 * Media class enum.
 *
 * Used to differentiate the type/class of media
 *
 * @package phpOMS\DataStorage\Database
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 */
abstract class MediaClass extends Enum
{
    public const FILE = 0;

    public const COLLECTION = 1;

    public const REFERENCE = 2;

    public const SYSTEM_FILE = 3;

    public const SYSTEM_DIRECTORY = 4;

}