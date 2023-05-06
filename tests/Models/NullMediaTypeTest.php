<?php
/**
 * Karaka
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

use Modules\Media\Models\NullMediaType;

/**
 * @internal
 */
final class NullMediaTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\NullMediaType
     * @group framework
     */
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\MediaType', new NullMediaType());
    }

    /**
     * @covers Modules\Media\Models\NullMediaType
     * @group framework
     */
    public function testId() : void
    {
        $null = new NullMediaType(2);
        self::assertEquals(2, $null->id);
    }
}
