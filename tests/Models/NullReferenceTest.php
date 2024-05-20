<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\tests\Models;

use Modules\Media\Models\NullReference;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Modules\Media\Models\NullReference::class)]
final class NullReferenceTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testNull() : void
    {
        self::assertInstanceOf('\Modules\Media\Models\Reference', new NullReference());
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testId() : void
    {
        $null = new NullReference(2);
        self::assertEquals(2, $null->id);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testJsonSerialize() : void
    {
        $null = new NullReference(2);
        self::assertEquals(['id' => 2], $null->jsonSerialize());
    }
}
