<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.2
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>
<div class="row">
    <div class="col-xs-12 col-md-6">
        <section class="portlet">
            <div class="portlet-head"><?= $this->getHtml('Upload', 'Media', 'Backend'); ?></div>
            <form id="<?= $this->form; ?>-upload">
                <div class="portlet-body">
                    <div class="form-group">
                        <label for="mediaInput"><?= $this->getHtml('Media', 'Media', 'Backend'); ?></label>
                        <div class="ipt-wrap">
                            <div class="ipt-first">
                                <div class="advIpt wf-100" id="iMediaInput">
                                    <input autocomplete="off" class="input" id="mediaInput" name="mediaFile" type="text"
                                        data-emptyAfter="true"
                                        data-autocomplete="off"
                                        data-src="api/media/find?search={!#mediaInput}">
                                    <div id="iMediaInput-popup" class="popup" data-active="true">
                                        <table id="iMediaInput-table" class="default sticky">
                                            <thead>
                                                <tr>
                                                    <td><?= $this->getHtml('ID', '0', '0'); ?><i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                                                    <td><?= $this->getHtml('Name', 'Media', 'Backend'); ?><i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                                            <tbody>
                                                <template id="iMediaInput-rowElement" class="rowTemplate">
                                                    <tr tabindex="-1">
                                                        <td data-tpl-text="/id" data-tpl-value="/id" data-value=""></td>
                                                        <td data-tpl-text="/name" data-tpl-value="/name" data-value=""></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="ipt-second"><button><?= $this->getHtml('Select', 'Media', 'Backend'); ?></button></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="iUpload"><?= $this->getHtml('Upload', 'Media', 'Backend'); ?></label>
                        <div class="wf-100">
                            <input type="hidden" name="virtualPath" form="<?= $this->form; ?>" value="<?= $this->virtualPath; ?>">
                            <input type="file" id="iUpload" name="upload" form="<?= $this->form; ?>" multiple>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="portlet">
            <div class="portlet-head"><?= $this->getHtml('Files', 'Media', 'Backend'); ?><i class="g-icon download btn end-xs">download</i></div>
                <div class="slider">
                <table id="iFiles" class="default sticky">
                    <thead>
                        <tr>
                            <td>
                            <td><?= $this->getHtml('ID', '0', '0'); ?><i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                            <td class="wf-100"><?= $this->getHtml('Name', 'Media', 'Backend'); ?><i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                            <td>
                    <tbody
                        id="iMediaInput-tags"
                        class="tags"
                        data-action='[{"listener": "change", "action": [{"key": 1, "type": "dom.set", "selector": "#iMediaFile", "value": "<?= UriFactory::build('{/api}media/export?csrf={$CSRF}') . '&id={!#iFiles [name=media_file]:checked}&type=html'; ?>"}]}]'
                        data-limit="0"
                        data-active="true"
                        data-form="<?= $this->form; ?>"
                    >
                        <template id="iMediaInput-tagTemplate">
                            <tr data-tpl-value="/id" data-value="" data-uuid="" data-name="media-list">
                                <td><label class="radio" for="iFile-0">
                                        <input id="iFile-0" type="radio" name="media_file" value="">
                                        <span class="checkmark"></span>
                                    </label>
                                <td data-tpl-text="/id" data-tpl-value="/id" data-value=""></td>
                                <td data-tpl-text="/name" data-tpl-value="/name" data-value=""></td>
                                <td>
                        </template>
                        <?php foreach ($this->files as $file) :
                            $url = UriFactory::build('{/base}/media/view?{?}&id=' . $file->id);
                        ?>
                            <tr data-tpl-value="/id" data-value="" data-uuid="" data-name="media-list">
                                <td><label class="radio" for="iFile-<?= $file->id; ?>">
                                        <input id="iFile-<?= $file->id; ?>" type="radio" name="media_file" value="<?= $file->id; ?>"<?= \end($this->files)->id === $file->id ? ' checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                <td data-tpl-text="/id" data-tpl-value="/id" data-value=""><?= $file->id; ?></td>
                                <td data-tpl-text="/name" data-tpl-value="/name" data-value=""><?= $this->printHtml($file->name); ?></td>
                                <td><a href="<?= $url; ?>"><i class="g-icon">attachment</i></a>
                        <?php endforeach; ?>
                        <?php if (empty($this->files)) : ?>
                            <tr>
                                <td colspan="4" class="empty"><?= $this->getHtml('Empty', '0', '0'); ?>
                        <?php endif; ?>
                </table>
            </div>
        </section>
    </div>

    <div class="col-xs-12 col-md-6 col-simple">
        <section id="mediaFile" class="portlet col-simple">
            <div class="portlet-body col-simple">
                <?php if (!empty($this->files)) : ?>
                    <iframe class="col-simple" id="iMediaFile" data-src="<?= UriFactory::build('{/api}media/export?csrf={$CSRF}') . '&id={!#iFiles [name=media_file]:checked}&type=html'; ?>" allowfullscreen></iframe>
                <?php else : ?>
                    <img width="100%" src="Web/Backend/img/logo_grey.png">
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
