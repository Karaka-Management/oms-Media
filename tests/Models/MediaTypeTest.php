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

use Modules\Media\Models\MediaType;
use phpOMS\Localization\BaseStringL11n;

/**
 * @internal
 */
final class MediaTypeTest extends \PHPUnit\Framework\TestCase
{
    private MediaType $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        $this->type = new MediaType();
    }

    /**
     * @covers \Modules\Media\Models\MediaType
     * @group module
     */
    public function testDefault() : void
    {
        self::assertEquals(0, $this->type->id);
        self::assertEquals('', $this->type->name);
    }

    /**
     * @covers \Modules\Media\Models\MediaType
     * @group module
     */
    public function testL11nInputOutput() : void
    {
        $this->type->setL11n('Test1');
        self::assertEquals('Test1', $this->type->getL11n());

        $this->type->setL11n(new BaseStringL11n('Test2'));
        self::assertEquals('Test2', $this->type->getL11n());
    }

    /**
     * @covers \Modules\Media\Models\MediaType
     * @group module
     */
    public function testSerialize() : void
    {
        $this->type->name = 'Name';

        $serialized = $this->type->jsonSerialize();
        unset($serialized['title']);

        self::assertEquals(
            [
                'id'   => 0,
                'name' => 'Name',
            ],
            $serialized
        );
    }
}
