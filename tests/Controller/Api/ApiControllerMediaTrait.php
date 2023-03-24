<?php
/**
 * Karaka
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

namespace Modules\Media\tests\Controller\Api;

use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\PathSettings;
use Modules\Media\Models\UploadStatus;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\HttpResponse;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\MimeType;
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

        $ids = [];
        foreach ($status as $stat) {
            $created = $this->module::createDbEntry(
                $stat,
                1
            );

            if ($created->getId() > 0) {
                $ids[] = $created->getId();
            }
        }

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
        $request->setData('tags', '[{"title": "TestTitle", "color": "#f0f", "language": "en"}, {"id": 1}]');

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
            $this->module->uploadFiles(
                names: ['test'],
                fileNames: ['test'],
                files: ['test'],
                account: 1,
                basePath: '/test',
            )
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

        $media = MediaMapper::get()->where('id', $id)->execute();
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

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testCreateView() : void
    {
        $media = new Media();

        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $response->header->lock();

        $request->header->account = 1;
        $request->setData('type', 'html');

        $media->extension = 'xls';
        self::assertInstanceOf('\phpOMS\Views\View', $this->module->createView($media, $request, $response));

       $media->extension = 'docx';
       self::assertInstanceOf('\phpOMS\Views\View', $this->module->createView($media, $request, $response));

       $request->setData('type', null, true);
       self::assertInstanceOf('\phpOMS\Views\View', $this->module->createView($media, $request, $response));
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportHTM() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'htm');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_HTML, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportPDF() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'pdf');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_PDF, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportC() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'c');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_TXT, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportTXT() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'txt');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_TXT, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportCSV() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'csv');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_CSV, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportXLS() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'xls');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_XLS, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportXLSX() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'xlsx');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_XLSX, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportDOC() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'doc');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_DOC, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportDOCX() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'docx');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_DOCX, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportPPT() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'ppt');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_PPT, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportPPTX() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'pptx');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_PPTX, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportJPG() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'jpg');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_JPG, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportGIF() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'gif');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_GIF, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportPNG() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'png');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_PNG, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportMP3() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'mp3');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_MP3, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportMP4() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'mp4');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_MP4, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportMPEG() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'mpeg');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_MPEG, $response->header->get('Content-Type')[0]);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiMediaExportBIN() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('id', '1');
        $request->setData('type', 'exe');

        $this->module->apiMediaExport($request, $response);
        self::assertEquals(MimeType::M_BIN, $response->header->get('Content-Type')[0]);
    }
}
