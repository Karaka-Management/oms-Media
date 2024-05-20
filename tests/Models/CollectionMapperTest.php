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

use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Collection;
use Modules\Media\Models\CollectionMapper;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Modules\Media\Models\CollectionMapper::class)]
final class CollectionMapperTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\Group('module')]
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

        self::assertGreaterThan(0, $media->id);
        self::assertEquals($id, $media->id);

        $mediaR = CollectionMapper::get()->where('id', $media->id)->execute();
        self::assertEquals($media->createdAt->format('Y-m-d'), $mediaR->createdAt->format('Y-m-d'));
        self::assertEquals($media->createdBy->id, $mediaR->createdBy->id);
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
