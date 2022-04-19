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

namespace Modules\Media\tests\Controller\Api;

use Modules\Admin\Models\NullAccount;
use Modules\Media\Models\Media;
use Modules\Media\Models\MediaMapper;
use Modules\Media\Models\NullCollection;
use phpOMS\Message\Http\HttpRequest;
use phpOMS\Message\Http\HttpResponse;
use phpOMS\Message\Http\RequestStatusCode;
use phpOMS\System\File\Local\Directory;
use phpOMS\Uri\HttpUri;
use phpOMS\Utils\TestUtils;

trait ApiControllerCollectionTrait
{
    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiCollectionCreateWitRandomPath() : void
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

        $media        = [];
        $createdMedia = $response->get('')['response'];
        foreach ($createdMedia as $file) {
            $media[] = $file;
        }

        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', 'Test Collection');
        $request->setData('virtualpath', '/');
        $request->setData('media-list', \json_encode($media));

        $this->module->apiCollectionCreate($request, $response);

        $collection = $response->get('')['response'];
        self::assertEquals('Test Collection', $collection->name);
        self::assertCount(2, $collection->getSources());
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiCollectionCreateInvalid() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;

        $this->module->apiCollectionCreate($request, $response);
        self::assertEquals(RequestStatusCode::R_400, $response->header->status);
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiCollectionCreateWithPath() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->header->account = 1;
        $request->setData('name', 'Test Collection');
        $request->setData('path', '/test/path');

        $this->module->apiCollectionCreate($request, $response);

        $collection = $response->get('')['response'];
        self::assertTrue(\is_dir(__DIR__ . '/../../../Files/test/path'));

        Directory::delete(__DIR__ . '/../../../Files/test/path');
    }

    /**
     * @covers Modules\Media\Controller\ApiController
     * @group module
     */
    public function testApiCollectionFromMedia() : void
    {
        $media                 = new Media();
        $media->createdBy      = new NullAccount(1);
        $media->description    = 'desc';
        $media->descriptionRaw = 'descRaw';
        $media->setPath('some/path');
        $media->size      = 11;
        $media->extension = 'png';
        $media->name      = 'Media for collection';
        $id               = MediaMapper::create()->execute($media);

        self::assertGreaterThan(0, $media->getId());
        self::assertEquals($id, $media->getId());

        $collection = $this->module->createMediaCollectionFromMedia('Collection With Media', '', [$media], 1);

        self::assertEquals('Collection With Media', $collection->name);
        self::assertCount(1, $collection->getSources());

        self::assertInstanceOf(
            NullCollection::class,
            $this->module->createMediaCollectionFromMedia('Collection With Media', '', [], 1)
        );

        Directory::delete(__DIR__ . '/../../../Files/test/path');
    }
}
