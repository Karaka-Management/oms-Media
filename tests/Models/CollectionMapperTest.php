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

use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;

/**
 * @internal
 */
class CollectionMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\CollectionMapper
     * @group module
     */
    public function testCR() : void
    {
        $media = new Collection();
        $media->setCreatedBy(new NullAccount(1));
        $media->setDescription('desc');
        $media->setDescriptionRaw('descRaw');
        $media->setPath('some/path');
        $media->setSize(11);
        $media->setName('Collection');
        $id = CollectionMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = CollectionMapper::get($media->getId());
        self::assertEquals($media->getCreatedAt()->format('Y-m-d'), $mediaR->getCreatedAt()->format('Y-m-d'));
        self::assertEquals($media->getCreatedBy()->getId(), $mediaR->getCreatedBy()->getId());
        self::assertEquals($media->getDescription(), $mediaR->getDescription());
        self::assertEquals($media->getDescriptionRaw(), $mediaR->getDescriptionRaw());
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute(), $mediaR->isAbsolute());
        self::assertEquals($media->getSize(), $mediaR->getSize());
        self::assertEquals($media->getExtension(), $mediaR->getExtension());
        self::assertEquals($media->getName(), $mediaR->getName());
    }
}
