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

use phpOMS\Uri\UriFactory;

/**
 * @var \phpOMS\Views\View $this
 */
?>
<div class="row">
    <div class="col-xs-12 col-md-8">
        <div class="portlet">
            <div class="portlet-body">
                <input autocomplete="off" form="fEditor" name="name" type="text" class="wf-100">
            </div>
        </div>

        <div class="portlet">
            <div class="portlet-body">
                <?= $this->getData('editor')->render('editor'); ?>
            </div>
        </div>

        <div class="box">
            <?= $this->getData('editor')->getData('text')->render('editor', 'content', 'fEditor'); ?>
        </div>
    </div>

    <div class="col-xs-12 col-md-4">
        <div class="box">
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/base}/media/list?path={?path}'); ?>"><?= $this->getHtml('Back'); ?></a>
        </div>

        <div class="portlet">
            <form id="fEditor" method="PUT" action="<?= UriFactory::build('{/api}media/file?{?}&csrf={$CSRF}'); ?>">
                <div class="portlet-head"><?= $this->getHtml('Settings'); ?></div>
                <div class="portlet-body">
                    <table class="layout wf-100">
                        <tr><td><label for="iVirtualPath"><?= $this->getHtml('VirtualPath'); ?></label>
                            <tr><td><input type="text" id="iVirtualPath" name="virtualPath" value="<?= empty($this->request->uri->getQuery('path')) ? '/' : $this->request->uri->getQuery('path'); ?>" disabled>
                            <tr><td><label for="iPath"><?= $this->getHtml('Path'); ?></label>
                            <tr><td><input type="text" id="iPath" name="path" value="<?= empty($this->request->uri->getQuery('path')) ? '/' : $this->request->uri->getQuery('path'); ?>">
                        <tr><td><label><?= $this->getHtml('Settings'); ?></label>
                        <tr><td>
                                <label class="checkbox" for="iAddCollection">
                                    <input type="checkbox" id="iAddCollection" name="addcollection" checked>
                                    <span class="checkmark"></span>
                                    <?= $this->getHtml('AddToCollection'); ?>
                                </label>
                    </table>
                </div>
                <div class="portlet-foot">
                    <input type="submit" value="<?= $this->getHtml('Save', '0', '0'); ?>" name="save-media-file">
                </div>
            </form>
        </div>

        <div class="portlet">
            <div class="portlet-body">
                <form>
                    <table class="layout">
                        <tr><td colspan="2"><label><?= $this->getHtml('Permission'); ?></label>
                        <tr><td><select name="permission">
                                    <option>
                                </select>
                        <tr><td colspan="2"><label><?= $this->getHtml('GroupUser'); ?></label>
                        <tr><td><input id="iPermission" name="group" type="text" placeholder=""><td><button><?= $this->getHtml('Add'); ?></button>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

