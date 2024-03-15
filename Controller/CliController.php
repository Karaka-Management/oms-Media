<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

namespace Modules\Media\Controller;

use Modules\Media\Models\MediaMapper;

/**
 * Media controller class.
 *
 * @package Modules\Media
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
final class CliController extends Controller
{
    /**
     * Api method to make a call to the cli app
     *
     * @param mixed ...$data Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function runEncryptionChangeFromHook(mixed ...$data) : void
    {
        $mapper = MediaMapper::yield()
            ->where('isEncrypted', true);

        foreach ($mapper->executeYield() as $media) {
            if (!empty($data['old'])) {
                $media->decrypt($data['old']);
            }

            if (!empty($data['new'])) {
                $media->encrypt($data['new']);
            }
        }
    }
}
