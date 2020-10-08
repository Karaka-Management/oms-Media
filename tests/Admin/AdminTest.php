<?php
/**
 * Orange Management
 *
 * PHP Version 7.4
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\tests\Admin;

use Modules\Media\Admin\Installer;

/**
 * @internal
 */
class AdminTest extends \PHPUnit\Framework\TestCase
{
    protected const MODULE_NAME = 'Media';

    protected const URI_LOAD = 'http://127.0.0.1/en/backend/media';

    use \Modules\tests\ModuleTestTrait;

    public function testInvalidMediaInstallPath() : void
    {
        $this->expectException(\phpOMS\System\File\PathException::class);

        Installer::installExternal($GLOBALS['dbpool'], ['path' => 'invalid.json']);
    }

    public function testInvalidMediaInstallJsonFile() : void
    {
        $this->expectException(\Exception::class);

        Installer::installExternal($GLOBALS['dbpool'], ['path' => 'invalidJson.json']);
    }
}
