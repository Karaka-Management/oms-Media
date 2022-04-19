<?php
/**
 * Karaka
 *
 * PHP Version 8.1
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
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaStatus;
use Modules\Tag\Models\Tag;

/**
 * @internal
 */
final class MediaTest extends \PHPUnit\Framework\TestCase
{
    protected Media $media;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        $this->media = new Media();
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testDefault() : void
    {
        self::assertEquals(0, $this->media->getId());
        self::assertEquals(0, $this->media->createdBy->getId());
        self::assertEquals((new \DateTime('now'))->format('Y-m-d'), $this->media->createdAt->format('Y-m-d'));
        self::assertEquals('', $this->media->extension);
        self::assertEquals('', $this->media->getPath());
        self::assertFalse($this->media->isAbsolute);
        self::assertEquals('', $this->media->name);
        self::assertEquals('', $this->media->description);
        self::assertEquals('', $this->media->descriptionRaw);
        self::assertEquals('/', $this->media->getVirtualPath());
        self::assertEquals(0, $this->media->size);
        self::assertFalse($this->media->isVersioned);
        self::assertFalse($this->media->compareNonce('something'));
        self::assertFalse($this->media->isEncrypted());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testCreatedByInputOutput() : void
    {
        $this->media->createdBy = new NullAccount(1);
        self::assertEquals(1, $this->media->createdBy->getId());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testExtensionInputOutput() : void
    {
        $this->media->extension = 'pdf';
        self::assertEquals('pdf', $this->media->extension);
    }

    /**
     * @covers Modules\Media\Models\Media
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
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testAbsolutePathInputOutput() : void
    {
        $this->media->isAbsolute = true;
        self::assertTrue($this->media->isAbsolute);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testNameInputOutput() : void
    {
        $this->media->name = 'Report';
        self::assertEquals('Report', $this->media->name);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testDescriptionInputOutput() : void
    {
        $this->media->description = 'This is a description';
        self::assertEquals('This is a description', $this->media->description);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testDescriptionRawInputOutput() : void
    {
        $this->media->descriptionRaw = 'This is a description raw';
        self::assertEquals('This is a description raw', $this->media->descriptionRaw);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testSizeInputOutput() : void
    {
        $this->media->size = 11;
        self::assertEquals(11, $this->media->size);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testVersionedInputOutput() : void
    {
        $this->media->isVersioned = true;
        self::assertTrue($this->media->isVersioned);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testVirtualPathInputOutput() : void
    {
        $this->media->setVirtualPath('/test/path');
        self::assertEquals('/test/path', $this->media->getVirtualPath());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testHiddenInputOutput() : void
    {
        $this->media->status = MediaStatus::HIDDEN;
        self::assertEquals(MediaStatus::HIDDEN, $this->media->status);
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testNonceInputOutput() : void
    {
        $this->media->setNonce('test');
        self::assertTrue($this->media->compareNonce('test'));
        self::assertFalse($this->media->compareNonce('test2'));
        self::assertTrue($this->media->isEncrypted());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testPasswordInputOutput() : void
    {
        $this->media->setPassword('test');
        self::assertTrue($this->media->comparePassword('test'));
        self::assertFalse($this->media->comparePassword('test2'));
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testTagInputOutput() : void
    {
        $tag = new Tag();
        $tag->setL11n('Tag');

        $this->media->addTag($tag);
        self::assertEquals($tag, $this->media->getTag(0));
        self::assertCount(1, $this->media->getTags());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testTagRemove() : void
    {
        $tag = new Tag();
        $tag->setL11n('Tag');

        $this->media->addTag($tag);
        self::assertTrue($this->media->removeTag(0));
        self::assertCount(0, $this->media->getTags());
        self::assertFalse($this->media->removeTag(0));
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testSerialize() : void
    {
        $this->media->createdBy = $acc = new NullAccount(1);
        $this->media->extension = 'pdf';
        $this->media->setPath('/home/root');
        $this->media->isAbsolute     = true;
        $this->media->name           = 'Report';
        $this->media->description    = 'This is a description';
        $this->media->descriptionRaw = 'This is a description raw';
        $this->media->size           = 11;
        $this->media->isVersioned    = true;
        $this->media->setVirtualPath('/test/path');
        $this->media->status = MediaStatus::HIDDEN;

        self::assertEquals($this->media->toArray(), $this->media->jsonSerialize());

        $arr = $this->media->toArray();
        unset($arr['createdAt']);
        self::assertEquals(
            [
                'id'             => 0,
                'createdBy'      => $acc,
                'name'           => 'Report',
                'description'    => 'This is a description',
                'descriptionRaw' => 'This is a description raw',
                'extension'      => 'pdf',
                'virtualpath'    => '/test/path',
                'size'           => 11,
                'status'         => MediaStatus::HIDDEN,
                'path'           => '/home/root',
                'absolute'       => true,
            ],
            $arr
        );
    }
}
