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

use Modules\Media\Models\NullMediaContent;

/**
 * @internal
 */
final class NullMediaContentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\NullMediaContent
     * @group module
     */
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\MediaContent', new NullMediaContent());
    }

    /**
     * @covers Modules\Media\Models\NullMediaContent
     * @group module
     */
    public function testId() : void
    {
        $null = new NullMediaContent(2);
        self::assertEquals(2, $null->id);
    }

    /**
     * @covers Modules\Media\Models\NullMediaContent
     * @group module
     */
    public function testJsonSerialize() : void
    {
        $null = new NullMediaContent(2);
        self::assertEquals(['id' => 2], $null->jsonSerialize());
    }
}
