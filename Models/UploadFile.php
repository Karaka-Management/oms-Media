<?php

/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use phpOMS\Log\FileLogger;
use phpOMS\Security\EncryptionHelper;
use phpOMS\System\File\Local\Directory;
use phpOMS\System\File\Local\File;

/**
 * Upload.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 * @codeCoverageIgnore
 */
class UploadFile
{
    /**
     * Limit of iterations to find a possible random path for the file to upload to.
     *
     * @var int
     * @since 1.0.0
     */
    private const PATH_GENERATION_LIMIT = 1000;

    /**
     * Image interlaced.
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $isInterlaced = true;

    /**
     * Upload max size.
     *
     * @var int
     * @since 1.0.0
     */
    public int $maxSize = 50000000;

    /**
     * Allowed mime types.
     *
     * @var string[]
     * @since 1.0.0
     */
    private $allowedTypes = [];

    /**
     * Output directory.
     *
     * @var string
     * @since 1.0.0
     */
    public string $outputDir = __DIR__ . '/../../Modules/Media/Files';

    /**
     * Output file name.
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $preserveFileName = true;

    /**
     * Upload file to server.
     *
     * @param array    $files         File data ($_FILE)
     * @param string[] $fileNames     File name
     * @param bool     $absolute      Use absolute path
     * @param string   $encryptionKey Encryption key
     * @param string   $encoding      Encoding used for uploaded file. Empty string will not convert file content.
     *
     * @return array
     *
     * @throws \Exception
     *
     * @since 1.0.0
     */
    public function upload(
        array $files,
        array $fileNames = [],
        bool $absolute = false,
        string $encryptionKey = '',
        string $encoding = 'UTF-8'
    ) : array
    {
        $result    = [];
        $fileNames = $this->preserveFileName || \count($files) !== \count($fileNames) ? [] : $fileNames;

        if (\count($files) === \count($files, \COUNT_RECURSIVE)) {
            $files = [$files];
        }

        if (!$absolute && \count($files) > 1) {
            $this->outputDir = $this->findOutputDir();
        }

        $fCounter = -1;
        foreach ($files as $key => $f) {
            if (!isset($f['name'], $f['tmp_name'], $f['size'])) {
                return [];
            }

            ++$fCounter;

            $path = $this->outputDir;

            $subdir = '';
            if (($last = \strripos($f['name'], '/')) !== false) {
                $subdir    = \substr($f['name'], 0, $last);
                $f['name'] = \substr($f['name'], $last + 1);
            }

            if (!\is_file($f['tmp_name'])) {
                $result[$key]['status'] = UploadStatus::FILE_NOT_FOUND;

                return $result;
            }

            if ($path === '') {
                $path = File::dirpath($f['tmp_name']);
            }

            if ($subdir !== '') {
                $path .= '/' . $subdir;
            }

            $result[$key]           = [];
            $result[$key]['status'] = UploadStatus::OK;

            if (!isset($f['error'])) {
                $result[$key]['status'] = UploadStatus::WRONG_PARAMETERS;

                return $result;
            } elseif ($f['error'] !== \UPLOAD_ERR_OK) {
                $result[$key]['status'] = $this->getUploadError($f['error']);

                return $result;
            }

            $result[$key]['size'] = $f['size'];

            if ($f['size'] > $this->maxSize) {
                $result[$key]['status'] = UploadStatus::CONFIG_SIZE;

                return $result;
            }

            if (!empty($this->allowedTypes) && !\in_array($f['type'], $this->allowedTypes, true)) {
                $result[$key]['status'] = UploadStatus::WRONG_EXTENSION;

                return $result;
            }

            $split                     = \explode('.', $f['name']);
            $result[$key]['filename']  = empty($fileNames) ? $f['name'] : $fileNames[$fCounter];
            $result[$key]['extension'] = ($c = \count($split)) > 1 ? $split[$c - 1] : '';

            if (!$this->preserveFileName || \is_file($path . '/' . $result[$key]['filename'])) {
                try {
                    $result[$key]['filename'] = $this->createFileName(
                        $path,
                        $this->preserveFileName ? $result[$key]['filename'] : '',
                        $result[$key]['extension']
                    );
                } catch (\Throwable $_) {
                    $result[$key]['filename'] = $f['name'];
                    $result[$key]['status']   = UploadStatus::FAILED_HASHING;

                    return $result;
                }
            }

            if (!\is_dir($path) && !Directory::create($path, 0755, true)) {
                FileLogger::getInstance()->error('Couldn\t upload media file. There maybe is a problem with your permission or uploaded file.');
            }

            if (!\rename($f['tmp_name'], $dest = $path . '/' . $result[$key]['filename'])) {
                $result[$key]['status'] = UploadStatus::NOT_MOVABLE;

                return $result;
            }

            if ($encryptionKey !== '') {
                $isEncrypted = EncryptionHelper::encryptFile($dest, $dest, $encryptionKey);

                if (!$isEncrypted) {
                    $result[$key]['status'] = UploadStatus::NOT_ENCRYPTABLE;

                    return $result;
                }
            }

            /*
            if ($this->isInterlaced && \in_array($extension, FileUtils::IMAGE_EXTENSION)) {
                //$this->interlace($extension, $dest);
            }
            */

            /*
            if ($encoding !== '') {
                // changing encoding bugs out image files
                //FileUtils::changeFileEncoding($dest, $encoding);
            }*/

            $result[$key]['path'] = $path;
        }

        return $result;
    }

    /**
     * Create file name if file already exists or if file name should be random.
     *
     * @param string $path      Path where file should be saved
     * @param string $tempName  Temp. file name generated during upload
     * @param string $extension Extension name
     *
     * @return string
     *
     * @throws \Exception This exception is thrown if the file couldn't be created
     *
     * @since 1.0.0
     */
    private function createFileName(string $path, string $tempName, string $extension) : string
    {
        $rnd   = '';
        $limit = -1;

        $nameWithoutExtension = empty($tempName)
            ? ''
            : (empty($extension)
                ? $tempName
                : \substr($tempName, 0, -\strlen($extension) - 1)
            );

        $fileName = $tempName;

        while (\is_file($path . '/' . $fileName)) {
            if ($limit >= self::PATH_GENERATION_LIMIT) {
                throw new \Exception('No file path could be found. Potential attack!');
            }

            ++$limit;
            $tempName = empty($nameWithoutExtension)
                ? \sha1($tempName . $rnd)
                : $nameWithoutExtension . ($limit === 1 ? '' : '_' . $rnd);

            $fileName = empty($extension)
                ? $tempName
                : $tempName . '.' . $extension;

            $rnd = \bin2hex(\random_bytes(3));
        }

        return $fileName;
    }

    /**
     * Make image interlace
     *
     * @param string $extension Image extension
     * @param string $path      File path
     *
     * @return void
     *
     * @since 1.0.0
     */
    /*
    private function interlace(string $extension, string $path) : void
    {
        if ($extension === 'png') {
            $img = \imagecreatefrompng($path);
        } elseif ($extension === 'jpg' || $extension === 'jpeg') {
            $img = \imagecreatefromjpeg($path);
        } else {
            $img = \imagecreatefromgif($path);
        }

        if ($img === false) {
            return;
        }

        \imageinterlace($img, $this->isInterlaced);

        if ($extension === 'png') {
            \imagepng($img, $path);
        } elseif ($extension === 'jpg' || $extension === 'jpeg') {
            \imagejpeg($img, $path);
        } else {
            \imagegif($img, $path);
        }

        \imagedestroy($img);
    }
    */

    /**
     * Find unique output path for batch of files
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function findOutputDir() : string
    {
        $rndPath = '';

        do {
            $rndPath = \str_pad(\dechex(\mt_rand(0, 65535)), 4, '0', \STR_PAD_LEFT);
        } while (\is_dir($this->outputDir . '/_' . $rndPath));

        return $this->outputDir . '/_' . $rndPath;
    }

    /**
     * Get upload error
     *
     * @param mixed $error Error type
     *
     * @return int
     *
     * @since 1.0.0
     */
    private function getUploadError($error) : int
    {
        switch ($error) {
            case \UPLOAD_ERR_NO_FILE:
                return UploadStatus::NOTHING_UPLOADED;
            case \UPLOAD_ERR_INI_SIZE:
            case \UPLOAD_ERR_FORM_SIZE:
                return UploadStatus::UPLOAD_SIZE;
            default:
                return UploadStatus::UNKNOWN_ERROR;
        }
    }

    /**
     * @return string[]
     *
     * @since 1.0.0
     */
    public function getAllowedTypes() : array
    {
        return $this->allowedTypes;
    }

    /**
     * @param string[] $allowedTypes Allowed file types
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setAllowedTypes(array $allowedTypes) : void
    {
        $this->allowedTypes = $allowedTypes;
    }

    /**
     * @param string $allowedTypes Allowed file types
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function addAllowedTypes(string $allowedTypes) : void
    {
        $this->allowedTypes[] = $allowedTypes;
    }
}
