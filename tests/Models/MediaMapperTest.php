<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
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
#[\PHPUnit\Framework\Attributes\CoversClass(\Modules\Media\Models\MediaMapper::class)]
final class MediaMapperTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\Group('module')]
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
        $id               = MediaMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->id);
        self::assertEquals($id, $media->id);

        $mediaR = MediaMapper::get()->where('id', $media->id)->execute();
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->id, $mediaR->createdBy->id);
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->descriptionRaw, $mediaR->descriptionRaw);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
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
        $id                = MediaMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->id);
        self::assertEquals($id, $media->id);

        $mediaR = MediaMapper::get()->where('id', $media->id)->execute();
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->id, $mediaR->createdBy->id);
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
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
        $id                = MediaMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->id);
        self::assertEquals($id, $media->id);

        $mediaR = MediaMapper::get()->where('id', $media->id)->execute();
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->id, $mediaR->createdBy->id);
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
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
        $id                = MediaMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->id);
        self::assertEquals($id, $media->id);

        $found  = MediaMapper::getByVirtualPath($media->getVirtualPath())->execute();
        $mediaR = \reset($found);
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->id, $mediaR->createdBy->id);
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
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
        $idCollection     = CollectionMapper::create()->execute($collection);

        self::assertGreaterThan(0, $collection->id);
        self::assertEquals($idCollection, $collection->id);

        $media              = new Media();
        $media->createdBy   = new NullAccount(1);
        $media->description = 'desc';
        $media->setPath('https://avatars0.githubusercontent.com/u/16034994');
        $media->setVirtualPath('/virtual/path/Collection');
        $media->isAbsolute = true;
        $media->size       = 11;
        $media->extension  = 'png';
        $media->name       = 'Absolute path';
        $idMedia           = MediaMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->id);
        self::assertEquals($idMedia, $media->id);

        $collectionR = MediaMapper::getParentCollection($collection->virtualPath . '/Collection/sub/file.txt')->execute();
        self::assertEquals($idCollection, $collectionR->id);
        self::assertEquals($collection->name, $collectionR->name);
    }
}
