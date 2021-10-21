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
use Modules\Media\Models\NullMedia;

/**
 * @internal
 */
final class CollectionTest extends \PHPUnit\Framework\TestCase
{
    protected Collection $media;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        $this->media = new Collection();
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testDefault() : void
    {
        self::assertEquals(0, $this->media->getId());
        self::assertEquals(0, $this->media->createdBy->getId());
        self::assertEquals((new \DateTime('now'))->format('Y-m-d'), $this->media->createdAt->format('Y-m-d'));
        self::assertEquals('collection', $this->media->extension);
        self::assertEquals('', $this->media->getPath());
        self::assertEquals('', $this->media->name);
        self::assertEquals('', $this->media->description);
        self::assertEquals(0, $this->media->size);
        self::assertFalse($this->media->isVersioned);
        self::assertEquals([], $this->media->getSources());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testCreatedByInputOutput() : void
    {
        $this->media->createdBy = new NullAccount(1);
        self::assertEquals(1, $this->media->createdBy->getId());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testExtensionInputOutput() : void
    {
        $this->media->extension = 'pdf';
        self::assertEquals('pdf', $this->media->extension);
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testPathInputOutput() : void
    {
        $this->media->setPath('/home/root');
        self::assertEquals('home/root', $this->media->getPath());

        $this->media->isAbsolute = true;
        self::assertEquals('/home/root', $this->media->getPath());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testDescriptionInputOutput() : void
    {
        $this->media->description = 'This is a description';
        self::assertEquals('This is a description', $this->media->description);
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testSizeInputOutput() : void
    {
        $this->media->size = 11;
        self::assertEquals(11, $this->media->size);
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testVersionedInputOutput() : void
    {
        $this->media->isVersioned = true;
        self::assertTrue($this->media->isVersioned);
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testSourceInputOutput() : void
    {
        $this->media->setSources([$a = new NullMedia(1), $b = new NullMedia(2), $c = new NullMedia(3)]);
        self::assertEquals([$a, $b, $c], $this->media->getSources());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testSourceAddInputOutput() : void
    {
        $this->media->setSources([$a = new NullMedia(1), $b = new NullMedia(2), $c = new NullMedia(3)]);
        $this->media->addSource($d = new NullMedia(4));
        self::assertEquals([$a, $b, $c, $d], $this->media->getSources());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testIteration() : void
    {
        $this->media->setSources([$a = new NullMedia(1), $b = new NullMedia(2), $c = new NullMedia(3)]);

        foreach ($this->media as $key => $media) {
            if ($media->getId() !== $key + 1) {
                self::assertEquals($key + 1, $media->getId());
            }
        }

        self::assertTrue(true);
    }
}
