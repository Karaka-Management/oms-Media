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

use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;

/**
 * Media type l11n mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://orange-management.org
 * @since   1.0.0
 */
final class MediaTypeL11nMapper extends DataMapperFactory
{
    /**
     * Columns.
     *
     * @var array<string, array{name:string, type:string, internal:string, autocomplete?:bool, readonly?:bool, writeonly?:bool, annotations?:array}>
     * @since 1.0.0
     */
    public const COLUMNS = [
        'media_type_l11n_id'       => ['name' => 'media_type_l11n_id',       'type' => 'int',    'internal' => 'id'],
        'media_type_l11n_title'    => ['name' => 'media_type_l11n_title',    'type' => 'string', 'internal' => 'title', 'autocomplete' => true],
        'media_type_l11n_type'     => ['name' => 'media_type_l11n_type',     'type' => 'int',    'internal' => 'type'],
        'media_type_l11n_language' => ['name' => 'media_type_l11n_language', 'type' => 'string', 'internal' => 'language'],
    ];

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    public const TABLE = 'media_type_l11n';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    public const PRIMARYFIELD ='media_type_l11n_id';
}
