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
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

/**
 * Reference class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 */
class Reference extends Media
{
    /**
     * Extension name.
     *
     * @var string
     * @since 1.0.0
     */
    public string $extension = 'reference';

    /**
     * Is reference.
     *
     * @var int
     * @since 1.0.0
     */
    public int $class = MediaClass::REFERENCE;
}
