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
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;

/**
 * Media type mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
final class MediaTypeMapper extends DataMapperFactory
{
    /**
     * Columns.
     *
     * @var array<string, array{name:string, type:string, internal:string, autocomplete?:bool, readonly?:bool, writeonly?:bool, annotations?:array}>
     * @since 1.0.0
     */
    public const COLUMNS = [
        'media_type_id'        => ['name' => 'media_type_id',        'type' => 'int',    'internal' => 'id'],
        'media_type_name'      => ['name' => 'media_type_name',      'type' => 'string', 'internal' => 'name'],
        'media_type_isvisible' => ['name' => 'media_type_isvisible', 'type' => 'bool',   'internal' => 'isVisible'],
    ];

    /**
     * Has many relation.
     *
     * @var array<string, array{mapper:class-string, table:string, self?:?string, external?:?string, column?:string}>
     * @since 1.0.0
     */
    public const HAS_MANY = [
        'title' => [
            'mapper'  => MediaTypeL11nMapper::class,
            'table'   => 'media_type_l11n',
            'self'    => 'media_type_l11n_type',
            'column'  => 'content',
            'external'=> null,
        ],
    ];

    /**
     * Model to use by the mapper.
     *
     * @var class-string
     * @since 1.0.0
     */
    public const MODEL = MediaType::class;

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    public const TABLE = 'media_type';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    public const PRIMARYFIELD ='media_type_id';
}
