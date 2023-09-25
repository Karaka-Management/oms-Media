<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\tests\Admin;

use Modules\Media\Admin\Installer;
use phpOMS\Application\ApplicationAbstract;

/**
 * @internal
 */
final class AdminTest extends \PHPUnit\Framework\TestCase
{
    protected const NAME = 'Media';

    protected const URI_LOAD = 'http://127.0.0.1/en/backend/media';

    use \tests\Modules\ModuleTestTrait;

    public function testInvalidMediaInstallPath() : void
    {
        $this->expectException(\phpOMS\System\File\PathException::class);

        $app = new class() extends ApplicationAbstract
        {
            protected string $appName = 'Api';
        };

        $app->dbPool = $GLOBALS['dbpool'];

        Installer::installExternal($app, ['path' => 'invalid.json']);
    }
}
