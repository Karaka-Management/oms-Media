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

use Modules\Media\Models\NullMedia;

/**
 * @internal
 */
final class NullMediaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\NullMedia
     * @group framework
     */
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\Media', new NullMedia());
    }

    /**
     * @covers Modules\Media\Models\NullMedia
     * @group framework
     */
    public function testId() : void
    {
        $null = new NullMedia(2);
        self::assertEquals(2, $null->id);
    }
}
