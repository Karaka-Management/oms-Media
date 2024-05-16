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

use Modules\Admin\Models\Account;
use Modules\Admin\Models\NullAccount;
use phpOMS\Security\EncryptionHelper;

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
class Media implements \JsonSerializable
{
    /**
     * ID.
     *
     * @var int
     * @since 1.0.0
     */
    public int $id = 0;

    /**
     * Name.
     *
     * @var string
     * @since 1.0.0
     */
    public string $name = '';

    /**
     * Content.
     *
     * @var null|MediaContent
     * @since 1.0.0
     */
    public ?MediaContent $content = null;

    /**
     * Extension.
     *
     * @var string
     * @since 1.0.0
     */
    public string $extension = '';

    /**
     * File size in bytes.
     *
     * @var int
     * @since 1.0.0
     */
    public int $size = 0;

    /**
     * Author.
     *
     * @var Account
     * @since 1.0.0
     */
    public Account $createdBy;

    /**
     * Uploaded.
     *
     * @var \DateTimeImmutable
     * @since 1.0.0
     */
    public \DateTimeImmutable $createdAt;

    /**
     * Resource path.
     *
     * @var string
     * @since 1.0.0
     */
    public string $path = '';

    /**
     * Virtual path.
     *
     * @var string
     * @since 1.0.0
     */
    public string $virtualPath = '/';

    /**
     * Is path absolute?
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $isAbsolute = false;

    /**
     * Is versioned.
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $isVersioned = false;

    /**
     * Media Description.
     *
     * @var string
     * @since 1.0.0
     */
    public string $description = '';

    /**
     * Media Description.
     *
     * @var string
     * @since 1.0.0
     */
    public string $descriptionRaw = '';

    /**
     * Resource id.
     *
     * @var null|Media
     * @since 1.0.0
     */
    public ?Media $source = null;

    /**
     * Is encrypted.
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $isEncrypted = false;

    /**
     * Media password hash.
     *
     * @var null|string
     * @since 1.0.0
     */
    public ?string $password = null;

    /**
     * Media is hidden.
     *
     * @var int
     * @since 1.0.0
     */
    public int $status = MediaStatus::NORMAL;

    /**
     * Media class.
     *
     * @var int
     * @since 1.0.0
     */
    public int $class = MediaClass::FILE;

    /**
     * Unit
     *
     * @var null|int
     * @since 1.0.0
     */
    public ?int $unit = null;

    /**
     * Language.
     *
     * @var null|string
     * @since 1.0.0
     */
    public ?string $language = null;

    /**
     * Country.
     *
     * @var null|string
     * @since 1.0.0
     */
    public ?string $country = null;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->createdBy = new NullAccount();
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Encrypt the media file
     *
     * @param string      $key        Password to encrypt the file with
     * @param null|string $outputPath Output path of the encryption (null = replace file)
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function encrypt(string $key, ?string $outputPath = null) : bool
    {
        return EncryptionHelper::encryptFile(
            $this->getAbsolutePath(),
            $outputPath ?? $this->getAbsolutePath(),
            $key
        );
    }

    /**
     * Decrypt the media file
     *
     * @param string      $key        Password to encrypt the file with
     * @param null|string $outputPath Output path of the encryption (null = replace file)
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function decrypt(string $key, ?string $outputPath = null) : bool
    {
        return EncryptionHelper::decryptFile(
            $this->getAbsolutePath(),
            $outputPath ?? $this->getAbsolutePath(),
            $key
        );
    }

    /**
     * Has password defined
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasPassword() : bool
    {
        return !empty($this->password);
    }

    /**
     * Set encryption password
     *
     * @param null|string $password Password
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setPassword(?string $password) : void
    {
        $this->password = empty($password) ? null : \password_hash($password, \PASSWORD_BCRYPT);
    }

    /**
     * Compare user password with password of the media file
     *
     * @param string $password User password
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function comparePassword(string $password) : bool
    {
        return \password_verify($password, $this->password ?? '');
    }

    /**
     * Get the media path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getPath() : string
    {
        return $this->isAbsolute ? $this->path : \ltrim($this->path, '\\/');
    }

    /**
     * Get the media path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getFileName() : string
    {
        return \basename($this->path);
    }

    /**
     * Get the media path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getExtension() : string
    {
        $pos = \strrpos('.', $this->path);

        if ($pos === false) {
            return '';
        }

        return \substr($this->path, $pos + 1);
    }

    /**
     * Get the absolute media path
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getAbsolutePath() : string
    {
        return $this->isAbsolute ? $this->path : __DIR__ . '/../../../' . \ltrim($this->path, '\\/');
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getVirtualPath() : string
    {
        return $this->virtualPath;
    }

    /**
     * @param string $path $filepath
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setPath(string $path) : void
    {
        $this->path = \rtrim(\strtr($path, '\\', '/'), '/');
    }

    /**
     * @param string $path $filepath
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setVirtualPath(string $path) : void
    {
        $this->virtualPath = \rtrim(\strtr($path, '\\', '/'), '/');
        if ($this->virtualPath === '') {
            $this->virtualPath = '/';
        }
    }

    /**
     * Has media tag by id
     *
     * @param int $id Media tag id
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasMediaTagId(int $id) : bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->id === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has media tag by name
     *
     * @param string $name Media tag name
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function hasMediaTagName(string $name) : bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'description'    => $this->description,
            'descriptionRaw' => $this->descriptionRaw,
            'extension'      => $this->extension,
            'virtualpath'    => $this->virtualPath,
            'size'           => $this->size,
            'status'         => $this->status,
            'path'           => $this->path,
            'absolute'       => $this->isAbsolute,
            'createdBy'      => $this->createdBy,
            'createdAt'      => $this->createdAt,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize() : mixed
    {
        return $this->toArray();
    }

    use \Modules\Tag\Models\TagListTrait;
}
