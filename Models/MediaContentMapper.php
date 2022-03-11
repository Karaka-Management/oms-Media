<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media\Models
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

namespace Modules\Media\Models;

use phpOMS\DataStorage\Database\Mapper\DataMapperFactory;

/**
 * Media mapper class.
 *
 * @package Modules\Media\Models
 * @license OMS License 1.0
 * @link    https://karaka.app
 * @since   1.0.0
 */
class MediaContentMapper extends DataMapperFactory
{
    /**
     * Columns.
     *
     * @var array<string, array{name:string, type:string, internal:string, autocomplete?:bool, readonly?:bool, writeonly?:bool, annotations?:array}>
     * @since 1.0.0
     */
    public const COLUMNS = [
        'media_parsed_id'      => ['name' => 'media_parsed_id',      'type' => 'int',    'internal' => 'id'],
        'media_parsed_content' => ['name' => 'media_parsed_content', 'type' => 'string', 'internal' => 'content'],
    ];

    /**
     * Model to use by the mapper.
     *
     * @var string
     * @since 1.0.0
     */
    public const MODEL = MediaContent::class;

    /**
     * Primary table.
     *
     * @var string
     * @since 1.0.0
     */
    public const TABLE = 'media_parsed';

    /**
     * Primary field name.
     *
     * @var string
     * @since 1.0.0
     */
    public const PRIMARYFIELD ='media_parsed_id';
}
