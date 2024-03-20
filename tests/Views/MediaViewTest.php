<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   tests
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\tests\Views;

use Modules\Media\Models\Media;
use Modules\Media\Views\MediaView;

/**
 * @internal
 */
final class MediaViewTest extends \PHPUnit\Framework\TestCase
{
    protected MediaView $view;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        $this->view = new MediaView();
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testFilePath() : void
    {
        $method = new \ReflectionMethod($this->view, 'filePathFunction');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__);
        self::assertEquals(
            \ltrim(__DIR__, '/'),
            $method->invoke($this->view, $media, '/sub/path')
        );

        $media->setPath(__DIR__);
        $media->isAbsolute = true;
        self::assertEquals(
            __DIR__ . '/MediaViewTest.php',
            $method->invoke($this->view, $media, '/MediaViewTest.php')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testDirPath() : void
    {
        $method = new \ReflectionMethod($this->view, 'dirPathFunction');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__);
        self::assertEquals(
            \ltrim(__DIR__, '/'),
            $method->invoke($this->view, $media, '/sub/path')
        );

        $media->setPath(\realpath(__DIR__ . '/../'));
        $media->isAbsolute = true;
        self::assertEquals(
            \realpath(__DIR__ . '/../Controller'),
            $method->invoke($this->view, $media, '/Controller')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testIscollection() : void
    {
        $method = new \ReflectionMethod($this->view, 'isCollectionFunction');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__);
        $media->isAbsolute = true;
        self::assertFalse(
            $method->invoke($this->view, $media, '/sub/path')
        );

        $media->setPath(__DIR__);
        $media->isAbsolute = true;
        $media->extension  = 'collection';
        self::assertFalse(
            $method->invoke($this->view, $media, '/MediaViewTest.php')
        );

        $media->setPath(__DIR__ . '/../');
        $media->isAbsolute = true;
        self::assertTrue(
            $method->invoke($this->view, $media, '/Views/')
        );

        $media->setPath(__DIR__);
        $media->isAbsolute = true;
        $media->extension  = 'collection';
        self::assertTrue(
            $method->invoke($this->view, $media, '/something')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testFileContent() : void
    {
        $method = new \ReflectionMethod($this->view, 'getFileContent');
        $method->setAccessible(true);

        self::assertEquals(
            "Line 1\nLine 2",
            $method->invoke($this->view, __DIR__ . '/test.md')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testInvalidFileContentPath() : void
    {
        $method = new \ReflectionMethod($this->view, 'getFileContent');
        $method->setAccessible(true);

        self::assertEquals(
            '',
            $method->invoke($this->view, __DIR__ . '/invalid.txt')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testFileLineContent() : void
    {
        $method = new \ReflectionMethod($this->view, 'lineContentFunction');
        $method->setAccessible(true);

        self::assertEquals(
            [
                'Line 1',
                'Line 2',
            ],
            $method->invoke($this->view, __DIR__ . '/test.md')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testInvalidFileLineContentPath() : void
    {
        $method = new \ReflectionMethod($this->view, 'lineContentFunction');
        $method->setAccessible(true);

        self::assertEquals(
            [],
            $method->invoke($this->view, __DIR__ . '/invalid.txt')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testIsImage() : void
    {
        $method = new \ReflectionMethod($this->view, 'isImageFile');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__ . '/test.md');
        $media->isAbsolute = true;
        self::assertFalse(
            $method->invoke($this->view, $media)
        );

        $media->setPath(__DIR__ . '/test.jpg');
        $media->extension  = 'jpg';
        $media->isAbsolute = true;
        self::assertTrue(
            $method->invoke($this->view, $media)
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testIsText() : void
    {
        $method = new \ReflectionMethod($this->view, 'isTextFile');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__ . '/test.jpg');
        $media->isAbsolute = true;
        self::assertFalse(
            $method->invoke($this->view, $media)
        );

        $media->setPath(__DIR__ . '/test.md');
        $media->extension  = 'md';
        $media->isAbsolute = true;
        self::assertTrue(
            $method->invoke($this->view, $media)
        );

        $media->setPath(__DIR__ . '/test.jpg');
        $media->extension  = 'jpg';
        $media->isAbsolute = true;
        self::assertTrue(
            $method->invoke($this->view, $media, __DIR__ . '/test.md')
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testIsVideo() : void
    {
        $method = new \ReflectionMethod($this->view, 'isVideoFile');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__ . '/Video.mp4');
        $media->isAbsolute = true;
        self::assertFalse(
            $method->invoke($this->view, $media)
        );
    }

    /**
     * @covers \Modules\Media\Views\MediaView
     * @group module
     */
    public function testIsAudio() : void
    {
        $method = new \ReflectionMethod($this->view, 'isAudioFile');
        $method->setAccessible(true);

        $media = new Media();
        $media->setPath(__DIR__ . '/Audio.mp4');
        $media->isAbsolute = true;
        self::assertFalse(
            $method->invoke($this->view, $media)
        );
    }
}
