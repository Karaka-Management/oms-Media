<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\tests\Models;

use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;

/**
 * @internal
 */
final class CollectionMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Modules\Media\Models\CollectionMapper
     * @group module
     */
    public function testCR() : void
    {
        $media                 = new Collection();
        $media->createdBy      = new NullAccount(1);
        $media->description    = 'desc';
        $media->descriptionRaw = 'descRaw';
        $media->setPath('some/path');
        $media->setVirtualPath('/some/path');
        $media->size = 11;
        $media->name = 'Collection';
        $id          = CollectionMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $mediaR = CollectionMapper::get()->where('id', $media->getId())->execute();
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->getId(), $mediaR->createdBy->getId());
        self::assertEquals($media->description, $mediaR->description);
        self::assertEquals($media->descriptionRaw, $mediaR->descriptionRaw);
        self::assertEquals($media->getPath(), $mediaR->getPath());
        self::assertEquals($media->isAbsolute, $mediaR->isAbsolute);
        self::assertEquals($media->size, $mediaR->size);
        self::assertEquals($media->extension, $mediaR->extension);
        self::assertEquals($media->name, $mediaR->name);

        self::assertGreaterThan(0, \count(CollectionMapper::getByVirtualPath('/some/path')->execute()));
        self::assertGreaterThan(0, \count(CollectionMapper::getCollectionsByPath('/', true)));
    }
}
