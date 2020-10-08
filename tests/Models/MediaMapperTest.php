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
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\CollectionMapper;
use Modules\Media\Models\Collection;

/**
 * @internal
 */
class MediaMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testCR() : void
    {
        $media = new Media();
        $media->setCreatedBy(new NullAccount(1));
        $media->setDescription('desc');
        $media->setDescriptionRaw('descRaw');
        $media->setPath('some/path');
        $media->setSize(11);
        $media->setExtension('png');
        $media->setName('Image');
        $id = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = MediaMapper::get($media->getId());
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

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testAbsolute() : void
    {
        $media = new Media();
        $media->setCreatedBy(new NullAccount(1));
        $media->setDescription('desc');
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->setAbsolute(true);
        $media->setSize(11);
        $media->setExtension('png');
        $media->setName('Absolute path');
        $id = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = MediaMapper::get($media->getId());
        self::assertEquals($media->getCreatedAt()->format('Y-m-d'), $mediaR->getCreatedAt()->format('Y-m-d'));
        self::assertEquals($media->getCreatedBy()->getId(), $mediaR->getCreatedBy()->getId());
        self::assertEquals($media->getDescription(), $mediaR->getDescription());
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute(), $mediaR->isAbsolute());
        self::assertEquals($media->getSize(), $mediaR->getSize());
        self::assertEquals($media->getExtension(), $mediaR->getExtension());
        self::assertEquals($media->getName(), $mediaR->getName());
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testDirectoryMapping() : void
    {
        $media = new Media();
        $media->setCreatedBy(new NullAccount(1));
        $media->setDescription('desc');
        $media->setPath(\realpath(__DIR__ . '/../../../../../'));
        $media->setAbsolute(true);
        $media->setSize(11);
        $media->setExtension('collection');
        $media->setName('Directory');
        $id = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = MediaMapper::get($media->getId());
        self::assertEquals($media->getCreatedAt()->format('Y-m-d'), $mediaR->getCreatedAt()->format('Y-m-d'));
        self::assertEquals($media->getCreatedBy()->getId(), $mediaR->getCreatedBy()->getId());
        self::assertEquals($media->getDescription(), $mediaR->getDescription());
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute(), $mediaR->isAbsolute());
        self::assertEquals($media->getSize(), $mediaR->getSize());
        self::assertEquals($media->getExtension(), $mediaR->getExtension());
        self::assertEquals($media->getName(), $mediaR->getName());
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testGetVirtualPath() : void
    {
        $media = new Media();
        $media->setCreatedBy(new NullAccount(1));
        $media->setDescription('desc');
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->setVirtualPath('/test/path');
        $media->setAbsolute(true);
        $media->setSize(11);
        $media->setExtension('png');
        $media->setName('Absolute path');
        $id = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $found  = MediaMapper::getByVirtualPath($media->getVirtualPath());
        $mediaR = \reset($found);
        self::assertEquals($media->getCreatedAt()->format('Y-m-d'), $mediaR->getCreatedAt()->format('Y-m-d'));
        self::assertEquals($media->getCreatedBy()->getId(), $mediaR->getCreatedBy()->getId());
        self::assertEquals($media->getDescription(), $mediaR->getDescription());
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute(), $mediaR->isAbsolute());
        self::assertEquals($media->getSize(), $mediaR->getSize());
        self::assertEquals($media->getExtension(), $mediaR->getExtension());
        self::assertEquals($media->getName(), $mediaR->getName());
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testParentcollection() : void
    {
        $collection = new Collection();
        $collection->setCreatedBy(new NullAccount(1));
        $collection->setDescription('desc');
        $collection->setDescriptionRaw('descRaw');
        $collection->setPath('some/path');
        $collection->setVirtualPath('/virtual/path');
        $collection->setSize(11);
        $collection->setName('Collection');
        $idCollection = CollectionMapper::create($collection);

        self::assertGreaterThan(0, $collection->getId());
        self::assertEquals($idCollection, $collection->getId());

        $media = new Media();
        $media->setCreatedBy(new NullAccount(1));
        $media->setDescription('desc');
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->setVirtualPath('/virtual/path/Collection');
        $media->setAbsolute(true);
        $media->setSize(11);
        $media->setExtension('png');
        $media->setName('Absolute path');
        $idMedia = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($idMedia, $media->getId());

        $collectionR = MediaMapper::getParentCollection($media->getVirtualPath());
        self::assertEquals($idCollection, $collectionR->getId());
        self::assertEquals($collection->getName(), $collectionR->getName());
    }
}
