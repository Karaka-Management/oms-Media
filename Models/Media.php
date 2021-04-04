<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use Modules\Admin\Models\Account;
use Modules\Admin\Models\NullAccount;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\NullTag;

/**
 * Media class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://orange-management.org
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
    protected int $id = 0;

    /**
     * Name.
     *
     * @var string
     * @since 1.0.0
     */
    public string $name = '';

    /**
     * Type.
     *
     * @var string
     * @since 1.0.0
     */
    public string $type = '';

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
    protected string $path = '';

    /**
     * Virtual path.
     *
     * @var string
     * @since 1.0.0
     */
    protected string $virtualPath = '/';

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
     * Media encryption nonce.
     *
     * @var null|string
     * @since 1.0.0
     */
    protected ?string $nonce = null;

    /**
     * Media password hash.
     *
     * @var null|string
     * @since 1.0.0
     */
    protected ?string $password = null;

    /**
     * Media is hidden.
     *
     * @var bool
     * @since 1.0.0
     */
    public bool $isHidden = false;

    /**
     * Is collection.
     *
     * @var int
     * @since 1.0.0
     */
    protected int $collection = 0;

    /**
     * Tags.
     *
     * @var Tag[]
     * @since 1.0.0
     */
    protected array $tags = [];

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
     * @return int
     *
     * @since 1.0.0
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Encrypt the media file
     *
     * @param string      $password   Password to encrypt the file with
     * @param null|string $outputPath Output path of the encryption (null = replace file)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function encrypt(string $password, string $outputPath = null) : string
    {
        return '';
    }

    /**
     * Decrypt the media file
     *
     * @param string      $password   Password to encrypt the file with
     * @param null|string $outputPath Output path of the encryption (null = replace file)
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function decrypt(string $password, string $outputPath = null) : string
    {
        return '';
    }

    /**
     * Set encryption nonce
     *
     * @param null|string $nonce Nonce from encryption password
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function setNonce(?string $nonce) : void
    {
        $this->nonce = $nonce;
    }

    /**
     * Is media file encrypted?
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function isEncrypted() : bool
    {
        return $this->nonce !== null;
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
        $temp = $password === null ? null : \password_hash($password, \PASSWORD_DEFAULT);

        $this->password = $temp === false ? null : $temp;
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
     * Compare nonce with encryption nonce of the media file
     *
     * @param string $nonce User nonce
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function compareNonce(string $nonce) : bool
    {
        return $this->nonce === null ? false : \hash_equals($this->nonce, $nonce);
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getPath() : string
    {
        return $this->isAbsolute ? $this->path : \ltrim($this->path, '\\/');
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
        $this->path = \str_replace('\\', '/', $path);
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
        $this->virtualPath = \str_replace('\\', '/', $path);
    }

    /**
     * Adding new tag.
     *
     * @param Tag $tag Tag
     *
     * @return int
     *
     * @since 1.0.0
     */
    public function addTag(Tag $tag) : int
    {
        $this->tags[] = $tag;

        \end($this->tags);
        $key = (int) \key($this->tags);
        \reset($this->tags);

        return $key;
    }

    /**
     * Remove Tag from list.
     *
     * @param int $id Tag
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function removeTag($id) : bool
    {
        if (isset($this->tags[$id])) {
            unset($this->tags[$id]);

            return true;
        }

        return false;
    }

    /**
     * Get task elements.
     *
     * @return Tag[]
     *
     * @since 1.0.0
     */
    public function getTags() : array
    {
        return $this->tags;
    }

    /**
     * Get task elements.
     *
     * @param int $id Element id
     *
     * @return Tag
     *
     * @since 1.0.0
     */
    public function getTag(int $id) : Tag
    {
        return $this->tags[$id] ?? new NullTag();
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
            'hidden'         => $this->isHidden,
            'path'           => $this->path,
            'absolute'       => $this->isAbsolute,
            'createdBy'      => $this->createdBy,
            'createdAt'      => $this->createdAt,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
