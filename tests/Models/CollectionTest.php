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
use Modules\Media\Models\NullMedia;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Modules\Media\Models\Collection::class)]
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

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testDefault() : void
    {
        self::assertEquals(0, $this->media->id);
        self::assertEquals(0, $this->media->createdBy->id);
        self::assertEquals((new \DateTime('now'))->format('Y-m-d'), $this->media->createdAt->format('Y-m-d'));
        self::assertEquals('collection', $this->media->extension);
        self::assertEquals('', $this->media->getPath());
        self::assertEquals('', $this->media->name);
        self::assertEquals('', $this->media->description);
        self::assertEquals(0, $this->media->size);
        self::assertFalse($this->media->isVersioned);
        self::assertEquals([], $this->media->getSources());
        self::assertInstanceOf('\Modules\Media\Models\NullMedia', $this->media->getSourceByName('invalid'));
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testCreatedByInputOutput() : void
    {
        $this->media->createdBy = new NullAccount(1);
        self::assertEquals(1, $this->media->createdBy->id);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testExtensionInputOutput() : void
    {
        $this->media->extension = 'pdf';
        self::assertEquals('pdf', $this->media->extension);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testPathInputOutput() : void
    {
        $this->media->setPath('/home/root');
        self::assertEquals('home/root', $this->media->getPath());

        $this->media->isAbsolute = true;
        self::assertEquals('/home/root', $this->media->getPath());
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testDescriptionInputOutput() : void
    {
        $this->media->description = 'This is a description';
        self::assertEquals('This is a description', $this->media->description);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testSizeInputOutput() : void
    {
        $this->media->size = 11;
        self::assertEquals(11, $this->media->size);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testVersionedInputOutput() : void
    {
        $this->media->isVersioned = true;
        self::assertTrue($this->media->isVersioned);
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testSourceInputOutput() : void
    {
        $this->media->setSources([$a = new NullMedia(1), $b = new NullMedia(2), $c = new NullMedia(3)]);

        $b->name = 'test';
        self::assertEquals([$a, $b, $c], $this->media->getSources());
        self::assertEquals($b, $this->media->getSourceByName('test'));
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testSourceAddInputOutput() : void
    {
        $this->media->setSources([$a = new NullMedia(1), $b = new NullMedia(2), $c = new NullMedia(3)]);
        $this->media->addSource($d = new NullMedia(4));
        self::assertEquals([$a, $b, $c, $d], $this->media->getSources());
    }

    #[\PHPUnit\Framework\Attributes\Group('module')]
    public function testIteration() : void
    {
        $this->media->setSources([$a = new NullMedia(1), $b = new NullMedia(2), $c = new NullMedia(3)]);

        foreach ($this->media as $key => $media) {
            if ($media->id !== $key + 1) {
                self::assertEquals($key + 1, $media->id);
            }
        }

        self::assertTrue(true);
    }
}
