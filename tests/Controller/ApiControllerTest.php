<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\tests\Controller;

use Model\CoreSettings;
use Modules\Admin\Models\AccountPermission;
use Modules\Media\tests\Controller\Api\ApiControllerCollectionTrait;
use Modules\Media\tests\Controller\Api\ApiControllerMediaTrait;
use phpOMS\Account\Account;
use phpOMS\Account\AccountManager;
use phpOMS\Account\PermissionType;
use phpOMS\Application\ApplicationAbstract;
use phpOMS\Dispatcher\Dispatcher;
use phpOMS\Event\EventManager;
use phpOMS\Localization\L11nManager;
use phpOMS\Module\ModuleManager;
use phpOMS\Router\WebRouter;
use phpOMS\Utils\TestUtils;

/**
 * @internal
 */
final class ApiControllerTest extends \PHPUnit\Framework\TestCase
{
    protected $app = null;

    protected $module = null;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        $this->app = new class() extends ApplicationAbstract
        {
            protected string $appName = 'Api';

            protected int $appId = 1;
        };

        $this->app->dbPool         = $GLOBALS['dbpool'];
        $this->app->unitId         = 1;
        $this->app->appName        = 'Api';
        $this->app->accountManager = new AccountManager($GLOBALS['session']);
        $this->app->appSettings    = new CoreSettings();
        $this->app->moduleManager  = new ModuleManager($this->app, __DIR__ . '/../../../Modules/');
        $this->app->dispatcher     = new Dispatcher($this->app);
        $this->app->eventManager   = new EventManager($this->app->dispatcher);
        $this->app->l11nManager    = new L11nManager();
        $this->app->eventManager->importFromFile(__DIR__ . '/../../../Web/Api/Hooks.php');

        $this->app->router = new WebRouter();

        $account = new Account();
        TestUtils::setMember($account, 'id', 1);

        $permission       = new AccountPermission();
        $permission->unit = 1;
        $permission->app  = 1;
        $permission->setPermission(
            PermissionType::READ
            | PermissionType::CREATE
            | PermissionType::MODIFY
            | PermissionType::DELETE
            | PermissionType::PERMISSION
        );

        $account->addPermission($permission);

        $this->app->accountManager->add($account);

        $this->module = $this->app->moduleManager->get('Media');
    }

    use ApiControllerMediaTrait;
    use ApiControllerCollectionTrait;
}
