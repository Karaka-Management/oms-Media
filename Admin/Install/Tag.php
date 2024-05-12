<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media\Admin\Install
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Admin\Install;

use phpOMS\Application\ApplicationAbstract;

/**
 * Tag class.
 *
 * @package Modules\Media\Admin\Install
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
class Tag
{
    /**
     * Install media providing
     *
     * @param ApplicationAbstract $app  Application
     * @param string              $path Module path
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function install(ApplicationAbstract $app, string $path) : void
    {
        \Modules\Tag\Admin\Installer::installExternal($app, ['path' => __DIR__ . '/Tag.install.json']);
    }
}
