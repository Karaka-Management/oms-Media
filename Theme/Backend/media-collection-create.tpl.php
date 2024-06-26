<?php
/**
* Jingga
*
* PHP Version 8.2
*
* @package   Modules\Media
* @copyright Dennis Eichhorn
* @license   OMS License 2.0
* @version   1.0.0
* @link      https://jingga.app
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
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/base}/media/list?path={?path}&csrf={$CSRF}'); ?>"><?= $this->getHtml('Back', '0', '0'); ?></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12 col-md-6">
        <form method="PUT" id="media-uploader" action="<?= UriFactory::build('{/api}media/collection?csrf={$CSRF}'); ?>">
            <div class="portlet">
                <div class="portlet-head"><?= $this->getHtml('CreateCollection'); ?></div>
                <div class="portlet-body">
                    <div class="form-group">
                        <label for="iVirtualPath"><?= $this->getHtml('VirtualPath'); ?></label>
                        <input type="text" id="iVirtualPath" name="virtualPath" value="<?= empty($this->request->uri->getQuery('path')) ? '/' : $this->request->uri->getQuery('path'); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="iPath"><?= $this->getHtml('Path'); ?></label>
                        <input type="text" id="iPath" name="path" value="<?= empty($this->request->uri->getQuery('path')) ? '/' : $this->request->uri->getQuery('path'); ?>">
                    </div>

                    <div class="form-group">
                        <label><?= $this->getHtml('Settings'); ?></label>
                        <label class="checkbox" for="iAddCollection">
                            <input type="checkbox" id="iAddCollection" name="addcollection" checked>
                            <span class="checkmark"></span>
                            <?= $this->getHtml('AddToCollection'); ?>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="iName"><?= $this->getHtml('Name'); ?></label>
                        <input type="text" id="iName" name="name" multiple>
                    </div>
                </div>
                <div class="portlet-foot">
                    <input type="submit" id="iMediaCreate" name="mediaCreateButton" value="<?= $this->getHtml('Create', '0', '0'); ?>">
                </div>
            </form>
        </div>
    </div>
</div>