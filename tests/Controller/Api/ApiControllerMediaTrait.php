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

namespace Modules\Media\tests\Controller\Api;

use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\PathSettings;
use Modules\Media\Models\UploadStatus;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\HttpResponse;
use phpOMS\System\File\Local\Directory;
use phpOMS\Uri\HttpUri;
use phpOMS\Utils\TestUtils;

trait ApiControllerMediaTrait
{
    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testCreateDbEntries() : void
    {
        $status = [
            [
                'status'    => UploadStatus::OK,
                'extension' => 'png',
                'filename'  => 'logo.png',
                'name'      => 'logo.png',
                'path'      => 'Modules/tests/Media/Files/',
                'size'      => 90210,
            ],
            [
                'status'    => UploadStatus::FAILED_HASHING,
                'extension' => 'png',
                'filename'  => 'logo.png',
                'name'      => 'logo.png',
                'path'      => 'Modules/tests/Media/Files/',
                'size'      => 90210,
            ],
            [
                'status'    => UploadStatus::OK,
                'extension' => 'png',
                'filename'  => 'logo2.png',
                'name'      => 'logo2.png',
                'path'      => 'Modules/tests/Media/Files/',
                'size'      => 90210,
            ],
        ];

        $ids = $this->module->createDbEntries($status, 1);
        self::assertCount(2, $ids);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaUploadRandomPath() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', 'Test Upload');

        if (!\is_dir(__DIR__ . '/temp')) {
            \mkdir(__DIR__ . '/temp');
        }

        \copy(__DIR__ . '/testFile1.txt', __DIR__ . '/temp/testFile1.txt');
        \copy(__DIR__ . '/testFile2.txt', __DIR__ . '/temp/testFile2.txt');

        $files = [
            [
                'error'    => \UPLOAD_ERR_OK,
                'type'     => 'txt',
                'name'     => 'testFile1.txt',
                'tmp_name' => __DIR__ . '/temp/testFile1.txt',
                'size'     => \filesize(__DIR__ . '/testFile1.txt'),
            ],
            [
                'error'    => \UPLOAD_ERR_OK,
                'type'     => 'txt',
                'name'     => 'testFile2.txt',
                'tmp_name' => __DIR__ . '/temp/testFile2.txt',
                'size'     => \filesize(__DIR__ . '/testFile2.txt'),
            ],
        ];

        TestUtils::setMember($request, 'files', $files);

        $this->module->apiMediaUpload($request, $response);

        if (\is_dir(__DIR__ . '/temp')) {
            Directory::delete(__DIR__ . '/temp');
        }

        $media = $response->get('')['response'];
        self::assertCount(2, $media);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaUploadDefinedPath() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', 'Test Upload');
        $request->setData('pathsettings', PathSettings::FILE_PATH);
        $request->setData('path', '/../tests/Controller/test/path'); // change path from Media/Files to this path
        $request->setData('virtualpath', '/other/path'); // Upload should be to this subdir in this path

        if (!\is_dir(__DIR__ . '/temp')) {
            \mkdir(__DIR__ . '/temp');
        }

        \copy(__DIR__ . '/testFile1.txt', __DIR__ . '/temp/testFile1.txt');
        \copy(__DIR__ . '/testFile2.txt', __DIR__ . '/temp/testFile2.txt');

        $files = [
            [
                'error'    => \UPLOAD_ERR_OK,
                'type'     => 'txt',
                'name'     => 'testFile1.txt',
                'tmp_name' => __DIR__ . '/temp/testFile1.txt',
                'size'     => \filesize(__DIR__ . '/testFile1.txt'),
            ],
            [
                'error'    => \UPLOAD_ERR_OK,
                'type'     => 'txt',
                'name'     => 'testFile2.txt',
                'tmp_name' => __DIR__ . '/temp/testFile2.txt',
                'size'     => \filesize(__DIR__ . '/testFile2.txt'),
            ],
        ];

        TestUtils::setMember($request, 'files', $files);

        $this->module->apiMediaUpload($request, $response);

        if (\is_dir(__DIR__ . '/temp')) {
            Directory::delete(__DIR__ . '/temp');
        }

        $media = $response->get('')['response'];
        self::assertTrue(\is_file(__DIR__ . '/../test/path/testFile1.txt'));
        self::assertTrue(\is_file(__DIR__ . '/../test/path/testFile2.txt'));

        Directory::delete(__DIR__ . '/../test');
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testUploadFilesInvalidPathSetting() : void
    {
        self::assertEquals(
            [],
            $this->module->uploadFiles(['test'], ['test'], ['test'], 1, '/test', '', null, '', '', 99)
        );
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaUpdate() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', 'Test Media');
        $request->setData('pathsettings', PathSettings::FILE_PATH);
        $request->setData('path', '/../tests/Controller/test/path'); // change path from Media/Files to this path
        $request->setData('virtualpath', '/other/path'); // Upload should be to this subdir in this path

        if (!\is_dir(__DIR__ . '/temp')) {
            \mkdir(__DIR__ . '/temp');
        }

        \copy(__DIR__ . '/testFile1.txt', __DIR__ . '/temp/testFile1.txt');
        \copy(__DIR__ . '/testFile2.txt', __DIR__ . '/temp/testFile2.txt');

        $files = [
            [
                'error'    => \UPLOAD_ERR_OK,
                'type'     => 'txt',
                'name'     => 'testFile1.txt',
                'tmp_name' => __DIR__ . '/temp/testFile1.txt',
                'size'     => \filesize(__DIR__ . '/testFile1.txt'),
            ],
            [
                'error'    => \UPLOAD_ERR_OK,
                'type'     => 'txt',
                'name'     => 'testFile2.txt',
                'tmp_name' => __DIR__ . '/temp/testFile2.txt',
                'size'     => \filesize(__DIR__ . '/testFile2.txt'),
            ],
        ];

        TestUtils::setMember($request, 'files', $files);
        $this->module->apiMediaUpload($request, $response);

        $id       = \reset($response->get('')['response']);
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', $id);
        $request->setData('name', 'Test Changed');
        $request->setData('content', 'Test Content');
        $this->module->apiMediaUpdate($request, $response);

        $media = MediaMapper::get($id);
        self::assertEquals('Test Changed', $media->name);
        self::assertEquals('Test Content', \file_get_contents(__DIR__ . '/../test/path/testFile1.txt'));

        Directory::delete(__DIR__ . '/../test');
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaCreateWithPath() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', 'Created File');
        $request->setData('content', 'file content');
        $request->setData('filename', 'created.md');
        $request->setData('path', '/../tests/Controller/test/path');

        $this->module->apiMediaCreate($request, $response);

        self::assertCount(1, $response->get('')['response']);
        self::assertTrue(\is_file(__DIR__ . '/../test/path/created.md'));

        Directory::delete(__DIR__ . '/../test');
    }
}
