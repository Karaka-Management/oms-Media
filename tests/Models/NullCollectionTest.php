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

namespace Modules\Media\tests\Models;

use Modules\Media\Models\NullCollection;

/**
 * @internal
 */
final class NullCollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\NullCollection
     * @group framework
     */
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\Collection', new NullCollection());
    }

    /**
     * @covers Modules\Media\Models\NullCollection
     * @group framework
     */
    public function testId() : void
    {
        $null = new NullCollection(2);
        self::assertEquals(2, $null->getId());
    }
}
