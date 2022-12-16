<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\tests\Models;

use Modules\Media\Models\NullMediaTypeL11n;

/**
 * @internal
 */
final class NullMediaTypeL11nTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\NullMediaTypeL11n
     * @group framework
     */
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\MediaTypeL11n', new NullMediaTypeL11n());
    }

    /**
     * @covers Modules\Media\Models\NullMediaTypeL11n
     * @group framework
     */
    public function testId() : void
    {
        $null = new NullMediaTypeL11n(2);
        self::assertEquals(2, $null->getId());
    }
}
