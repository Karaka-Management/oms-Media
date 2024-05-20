<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media\Admin
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Admin;

use phpOMS\Module\UninstallerAbstract;

/**
 * Uninstaller class.
 *
 * @package Modules\Media\Admin
 * @license OMS License 2.2
 * @link    https://jingga.app
 * @since   1.0.0
 */
final class Uninstaller extends UninstallerAbstract
{
    /**
     * Path of the file
     *
     * @var string
     * @since 1.0.0
     */
    public const PATH = __DIR__;
}
