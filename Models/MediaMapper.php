<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use Modules\Admin\Models\AccountMapper;
use Modules\Tag\Models\TagMapper;
use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;
use phpOMS\DataStorage\Database\Mapper\ReadMapper;

/**
 * Media mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 */
class MediaMapper extends DataMapperFactory
{
    /**
     * Columns.
     *
     * @var array<string, array{name:string, type:string, internal:string, autocomplete?:bool, readonly?:bool, writeonly?:bool, annotations?:array}>
     * @since 1.0.0
     */
    public const COLUMNS = [
        'media_id'              => ['name' => 'media_id',              'type' => 'int',               'internal' => 'id'],
        'media_name'            => ['name' => 'media_name',            'type' => 'string',            'internal' => 'name',        'autocomplete' => true],
        'media_type'            => ['name' => 'media_type',            'type' => 'int',               'internal' => 'type'],
        'media_description'     => ['name' => 'media_description',     'type' => 'string',            'internal' => 'description', 'autocomplete' => true],
        'media_description_raw' => ['name' => 'media_description_raw', 'type' => 'string',            'internal' => 'descriptionRaw'],
        'media_content'         => ['name' => 'media_content', 'type' => 'int',            'internal' => 'content'],
        'media_versioned'       => ['name' => 'media_versioned',       'type' => 'bool',              'internal' => 'isVersioned'],
        'media_status'          => ['name' => 'media_status',          'type' => 'bool',              'internal' => 'status'],
        'media_file'            => ['name' => 'media_file',            'type' => 'string',            'internal' => 'path',        'autocomplete' => true],
        'media_virtual'         => ['name' => 'media_virtual',         'type' => 'string',            'internal' => 'virtualPath', 'autocomplete' => true],
        'media_absolute'        => ['name' => 'media_absolute',        'type' => 'bool',              'internal' => 'isAbsolute'],
        'media_nonce'           => ['name' => 'media_nonce',           'type' => 'string',            'internal' => 'nonce'],
        'media_password'        => ['name' => 'media_password',        'type' => 'string',            'internal' => 'password'],
        'media_extension'       => ['name' => 'media_extension',       'type' => 'string',            'internal' => 'extension'],
        'media_size'            => ['name' => 'media_size',            'type' => 'int',               'internal' => 'size'],
        'media_source'          => ['name' => 'media_source',      'type' => 'int',              'internal' => 'source'],
        'media_class'           => ['name' => 'media_class',      'type' => 'int',              'internal' => 'class'],
        'media_created_by'      => ['name' => 'media_created_by',      'type' => 'int',               'internal' => 'createdBy',   'readonly' => true],
        'media_created_at'      => ['name' => 'media_created_at',      'type' => 'DateTimeImmutable', 'internal' => 'createdAt',   'readonly' => true],
    ];

    /**
     * Belongs to.
     *
     * @var array<string, array{mapper:string, external:string, column?:string, by?:string}>
     * @since 1.0.0
     */
    public const BELONGS_TO = [
        'createdBy' => [
            'mapper'   => AccountMapper::class,
            'external' => 'media_created_by',
        ],
    ];

    /**
     * Belongs to.
     *
     * @var array<string, array{mapper:string, external:string, by?:string, column?:string, conditional?:bool}>
     * @since 1.0.0
     */
    public const OWNS_ONE = [
        'type' => [
            'mapper'   => MediaTypeMapper::class,
            'external' => 'media_type',
        ],
        'source' => [
            'mapper'   => self::class,
            'external' => 'media_source',
        ],
        'content' => [
            'mapper'   => MediaContentMapper::class,
            'external' => 'media_content',
        ],
    ];

    /**
     * Has many relation.
     *
     * @var array<string, array{mapper:string, table:string, self?:?string, external?:?string, column?:string}>
     * @since 1.0.0
     */
    public const HAS_MANY = [
        'tags'         => [
            'mapper'   => TagMapper::class,
            'table'    => 'media_tag',
            'external' => 'media_tag_dst',
            'self'     => 'media_tag_src',
        ],
    ];

    /**
     * Model to use by the mapper.
     *
     * @var string
     * @since 1.0.0
     */
    public const MODEL = Media::class;

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    public const TABLE = 'media';

    /**
     * Created at.
     *
     * @var string
     * @since 1.0.0
     */
    public const CREATED_AT = 'media_created_at';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    public const PRIMARYFIELD ='media_id';

    /**
     * Get media based on virtual path.
     *
     * The virtual path is equivalent to the directory path on a file system.
     *
     * A media model also has a file path, this however doesn't have to be the same as the virtual path
     * and in fact most of the time it is different. This is because the location on a hard drive or web
     * drive should not have any impact on the media file/media structure in the application.
     *
     * As a result media files are structured by virutal path in the app, by file path on the file system
     * and by Collections which can have sub-collections as well. Collections allow to reference files
     * in a different virtual path and are therfore similar to "symlinks", except that they don't reference
     * a file but create a new virtual media model which groups other media models together in a new virtual
     * path if so desired without deleting or moving the orginal media files.
     *
     * @param string $virtualPath Virtual path
     * @param int    $status      Media status
     *
     * @return ReadMapper
     *
     * @since 1.0.0
     */
    public static function getByVirtualPath(string $virtualPath = '/', int $status = MediaStatus::NORMAL) : ReadMapper
    {
        return self::getAll()
            ->with('createdBy')
            ->with('source')
            ->with('tags')
            ->with('tags/title')
            ->where('virtualPath', $virtualPath)
            ->where('status', $status);
    }

    /**
     * Get parent collection
     *
     * @param string $path Virtual path
     *
     * @return ReadMapper
     *
     * @since 1.0.0
     */
    public static function getParentCollection(string $path = '/') : ReadMapper
    {
        $virtualPath = '/' . \trim(\substr($path, 0, \strripos($path, '/') + 1), '/');
        $name        = \substr($path, \strripos($path, '/') + 1);

        return CollectionMapper::get()
            ->with('sources')
            ->with('source')
            ->where('virtualPath', $virtualPath)
            ->where('class', MediaClass::COLLECTION)
            ->where('name', $name);
    }
}
