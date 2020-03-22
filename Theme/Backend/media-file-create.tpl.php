<?php
/**
* Orange Management
*
* PHP Version 7.4
*
* @package   Modules\Media
* @copyright Dennis Eichhorn
* @license   OMS License 1.0
* @version   1.0.0
* @link      https://orange-management.org
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

/**
 * @var \phpOMS\Views\View $this
 */
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <a class="button" href="<?= UriFactory::build('{/prefix}media/list?path={?path}'); ?>">Back</a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="portlet">
            <div class="portlet-body">
                <form id="fEditor" method="PUT" action="<?= UriFactory::build('{/api}media/file?{?}&csrf={$CSRF}'); ?>">
                    <div class="ipt-wrap">
                        <div class="ipt-first"><input name="title" type="text" class="wf-100"></div>
                        <div class="ipt-second"><input type="submit" value="<?= $this->getHtml('Save', '0', '0') ?>"></div>
                    </div>
                </form>
            </div>
        </div>

        <div class="portlet">
            <div class="portlet-body">
                <?= $this->getData('editor')->render('editor'); ?>
            </div>
        </div>

        <div class="box">
            <?= $this->getData('editor')->getData('text')->render('editor', 'plain', 'fEditor'); ?>
        </div>
    </div>
</div>
