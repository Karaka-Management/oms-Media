<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
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
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;

/**
 * @internal
 */
final class MediaMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testCR() : void
    {
        $media                 = new Media();
        $media->createdBy      = new NullAccount(1);
        $media->description    = 'desc';
        $media->descriptionRaw = 'descRaw';
        $media->setPath('some/path');
        $media->size      = 11;
        $media->extension = 'png';
        $media->name      = 'Image';
        $id               = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = MediaMapper::get($media->getId());
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->getId(), $mediaR->createdBy->getId());
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->descriptionRaw, $mediaR->descriptionRaw);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testAbsolute() : void
    {
        $media              = new Media();
        $media->createdBy   = new NullAccount(1);
        $media->description = 'desc';
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->isAbsolute = true;
        $media->size       = 11;
        $media->extension  = 'png';
        $media->name       = 'Absolute path';
        $id                = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = MediaMapper::get($media->getId());
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->getId(), $mediaR->createdBy->getId());
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testDirectoryMapping() : void
    {
        $media              = new Media();
        $media->createdBy   = new NullAccount(1);
        $media->description = 'desc';
        $media->setPath(\realpath(__DIR__ . '/../../../../../'));
        $media->isAbsolute = true;
        $media->size       = 11;
        $media->extension  = 'collection';
        $media->name       = 'Directory';
        $id                = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = MediaMapper::get($media->getId());
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->getId(), $mediaR->createdBy->getId());
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testGetVirtualPath() : void
    {
        $media              = new Media();
        $media->createdBy   = new NullAccount(1);
        $media->description = 'desc';
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->setVirtualPath('/mediamappertest/path');
        $media->isAbsolute = true;
        $media->size       = 11;
        $media->extension  = 'png';
        $media->name       = 'With virtual path';
        $id                = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $found  = MediaMapper::getByVirtualPath($media->getVirtualPath());
        $mediaR = \reset($found);
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->getId(), $mediaR->createdBy->getId());
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    /**
     * @covers Modules\Media\Models\MediaMapper
     * @group module
     */
    public function testParentcollection() : void
    {
        $collection                 = new Collection();
        $collection->createdBy      = new NullAccount(1);
        $collection->description    = 'desc';
        $collection->descriptionRaw = 'descRaw';
        $collection->setPath('some/path');
        $collection->setVirtualPath('/virtual/path');
        $collection->size = 11;
        $collection->name = 'Collection';
        $idCollection     = CollectionMapper::create($collection);

        self::assertGreaterThan(0, $collection->getId());
        self::assertEquals($idCollection, $collection->getId());

        $media              = new Media();
        $media->createdBy   = new NullAccount(1);
        $media->description = 'desc';
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->setVirtualPath('/virtual/path/Collection');
        $media->isAbsolute = true;
        $media->size       = 11;
        $media->extension  = 'png';
        $media->name       = 'Absolute path';
        $idMedia           = MediaMapper::create($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($idMedia, $media->getId());

        $collectionR = MediaMapper::getParentCollection($media->getVirtualPath());
        self::assertEquals($idCollection, $collectionR->getId());
        self::assertEquals($collection->name, $collectionR->name);
    }
}
