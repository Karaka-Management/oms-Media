<?php
/**
 * Karaka
 *
 * PHP Version 8.1
 *
 * @package   Modules\News
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;
?>

<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <form id="mediaPassword" method="GET" action="<?= UriFactory::build('{%}'); ?>">
            <div class="form-group">
                <label for="iPassword"><?= $this->getHtml('Password'); ?></label>
                <input id="iPassword" type="password" name="password">
            </div>

            <input type="submit" value="<?= $this->getHtml('Submit', '0', '0'); ?>">
        </form>
    </div>
</section>