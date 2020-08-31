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

namespace Modules\Media\tests\Controller\Api;

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
    public function testApiMediaUpload() : void
    {
        $response = new HttpResponse();
        $request  = new HttpRequest(new HttpUri(''));

        $request->getHeader()->setAccount(1);
        $request->setData('name', 'Test Upload');

        if (!\file_exists(__DIR__ . '/temp')) {
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

        if (\file_exists(__DIR__ . '/temp')) {
            Directory::delete(__DIR__ . '/temp');
        }

        $media = $response->get('')['response'];
        self::assertCount(2, $media);
    }
}
