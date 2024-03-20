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
 * Reference class.
 *
 * This class represents a reference to a media file. It extends the Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
class Reference extends Media
{
    /**
     * The file extension of the reference file.
     *
     * @var string
     * @since 1.0.0
     */
    public string $extension = 'reference';

    /**
     * The media class of the reference.
     *
     * @var int
     * @since 1.0.0
     */
    public int $class = MediaClass::REFERENCE;
}
