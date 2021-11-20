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

use phpOMS\DataStorage\Database\DataMapperAbstract;

/**
 * Media type mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class MediaTypeMapper extends DataMapperAbstract
{
    /**
     * Columns.
     *
     * @var array<string, array{name:string, type:string, internal:string, autocomplete?:bool, readonly?:bool, writeonly?:bool, annotations?:array}>
     * @since 1.0.0
     */
    protected static array $columns = [
        'media_type_id'    => ['name' => 'media_type_id',    'type' => 'int',    'internal' => 'id'],
        'media_type_name'  => ['name' => 'media_type_name', 'type' => 'string', 'internal' => 'name'],
        'media_type_isvisible'  => ['name' => 'media_type_isvisible', 'type' => 'bool', 'internal' => 'isVisible'],
    ];

    /**
     * Has many relation.
     *
     * @var array<string, array{mapper:string, table:string, self?:?string, external?:?string, column?:string}>
     * @since 1.0.0
     */
    protected static array $hasMany = [
        'title' => [
            'mapper'            => MediaTypeL11nMapper::class,
            'table'             => 'media_type_l11n',
            'self'              => 'media_type_l11n_type',
            'column'            => 'title',
            'conditional'       => true,
            'external'          => null,
        ],
    ];

    /**
     * Model to use by the mapper.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $model = MediaType::class;

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $table = 'media_type';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    protected static string $primaryField = 'media_type_id';
}
