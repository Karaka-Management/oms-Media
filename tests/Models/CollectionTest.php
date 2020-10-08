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
use Modules\Media\Models\NullMedia;

/**
 * @internal
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    protected Collection $media;

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
        self::assertEquals(0, $this->media->getCreatedBy()->getId());
        self::assertEquals((new \DateTime('now'))->format('Y-m-d'), $this->media->getCreatedAt()->format('Y-m-d'));
        self::assertEquals('collection', $this->media->getExtension());
        self::assertEquals('', $this->media->getPath());
        self::assertEquals('', $this->media->getName());
        self::assertEquals('', $this->media->getDescription());
        self::assertEquals(0, $this->media->getSize());
        self::assertFalse($this->media->isVersioned());
        self::assertEquals([], $this->media->getSources());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testCreatedByInputOutput() : void
    {
        $this->media->setCreatedBy(new NullAccount(1));
        self::assertEquals(1, $this->media->getCreatedBy()->getId());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testExtensionInputOutput() : void
    {
        $this->media->setExtension('pdf');
        self::assertEquals('collection', $this->media->getExtension());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testPathInputOutput() : void
    {
        $this->media->setPath('/home/root');
        self::assertEquals('home/root', $this->media->getPath());

        $this->media->setAbsolute(true);
        self::assertEquals('/home/root', $this->media->getPath());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testDescriptionInputOutput() : void
    {
        $this->media->setDescription('This is a description');
        self::assertEquals('This is a description', $this->media->getDescription());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testSizeInputOutput() : void
    {
        $this->media->setSize(11);
        self::assertEquals(11, $this->media->getSize());
    }

    /**
     * @covers Modules\Media\Models\Collection
     * @group module
     */
    public function testVersionedInputOutput() : void
    {
        $this->media->setVersioned(true);
        self::assertFalse($this->media->isVersioned());
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
