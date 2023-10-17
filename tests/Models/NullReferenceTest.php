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

namespace Modules\Media\tests\Models;

use Modules\Media\Models\NullReference;

/**
 * @internal
 */
final class NullReferenceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\NullReference
     * @group module
     */
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\Reference', new NullReference());
    }

    /**
     * @covers Modules\Media\Models\NullReference
     * @group module
     */
    public function testId() : void
    {
        $null = new NullReference(2);
        self::assertEquals(2, $null->id);
    }

    /**
     * @covers Modules\Media\Models\NullReference
     * @group module
     */
    public function testJsonSerialize() : void
    {
        $null = new NullReference(2);
        self::assertEquals(['id' => 2], $null->jsonSerialize());
    }
}
