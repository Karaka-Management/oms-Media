<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\News
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>

<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <form id="mediaPassword" method="GET" action="<?= UriFactory::build('{%}&csrf={$CSRF}'); ?>">
            <div class="form-group">
                <label for="iPassword"><?= $this->getHtml('Password'); ?></label>
                <input id="iPassword" type="password" name="password">
            </div>

            <input type="submit" value="<?= $this->getHtml('Submit', '0', '0'); ?>">
        </form>
    </div>
</section>