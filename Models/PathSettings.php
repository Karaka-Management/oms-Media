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

use phpOMS\Stdlib\Base\Enum;

/**
 * Path settings enum.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
abstract class PathSettings extends Enum
{
    public const FILE_PATH = 1;

    public const RANDOM_PATH = 2;
}
