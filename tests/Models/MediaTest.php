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

/**
 * @internal
 */
class MediaTest extends \PHPUnit\Framework\TestCase
{
    protected Media $media;

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
        self::assertEquals(0, $this->media->getCreatedBy()->getId());
        self::assertEquals((new \DateTime('now'))->format('Y-m-d'), $this->media->getCreatedAt()->format('Y-m-d'));
        self::assertEquals('', $this->media->getExtension());
        self::assertEquals('', $this->media->getPath());
        self::assertFalse($this->media->isAbsolute());
        self::assertEquals('', $this->media->getName());
        self::assertEquals('', $this->media->getDescription());
        self::assertEquals('', $this->media->getDescriptionRaw());
        self::assertEquals('/', $this->media->getVirtualPath());
        self::assertEquals(0, $this->media->getSize());
        self::assertFalse($this->media->isVersioned());
        self::assertFalse($this->media->compareNonce('something'));
        self::assertFalse($this->media->isEncrypted());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testCreatedByInputOutput() : void
    {
        $this->media->setCreatedBy(new NullAccount(1));
        self::assertEquals(1, $this->media->getCreatedBy()->getId());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testExtensionInputOutput() : void
    {
        $this->media->setExtension('pdf');
        self::assertEquals('pdf', $this->media->getExtension());
    }

    /**
     * @covers Modules\Media\Models\Media
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
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testAbsolutePathInputOutput() : void
    {
        $this->media->setAbsolute(true);
        self::assertTrue($this->media->isAbsolute());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testNameInputOutput() : void
    {
        $this->media->setName('Report');
        self::assertEquals('Report', $this->media->getName());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testDescriptionInputOutput() : void
    {
        $this->media->setDescription('This is a description');
        self::assertEquals('This is a description', $this->media->getDescription());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testDescriptionRawInputOutput() : void
    {
        $this->media->setDescriptionRaw('This is a description raw');
        self::assertEquals('This is a description raw', $this->media->getDescriptionRaw());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testSizeInputOutput() : void
    {
        $this->media->setSize(11);
        self::assertEquals(11, $this->media->getSize());
    }

    /**
     * @covers Modules\Media\Models\Media
     * @group module
     */
    public function testVersionedInputOutput() : void
    {
        $this->media->setVersioned(true);
        self::assertTrue($this->media->isVersioned());
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
        $this->media->setHidden(true);
        self::assertTrue($this->media->isHidden());
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
    public function testSerialize() : void
    {
        $this->media->setCreatedBy($acc = new NullAccount(1));
        $this->media->setExtension('pdf');
        $this->media->setPath('/home/root');
        $this->media->setAbsolute(true);
        $this->media->setName('Report');
        $this->media->setDescription('This is a description');
        $this->media->setDescriptionRaw('This is a description raw');
        $this->media->setSize(11);
        $this->media->setVersioned(true);
        $this->media->setVirtualPath('/test/path');
        $this->media->setHidden(true);

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
                'hidden'         => true,
                'path'           => '/home/root',
                'absolute'       => true,
            ],
            $arr
        );
    }
}
