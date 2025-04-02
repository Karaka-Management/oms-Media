<div class="ipt-wrap">
    <div class="ipt-first">
        <span class="input">
            <button type="button" id="<?= $this->id; ?>-book-button" data-action='[
                {
                    "key": 1, "listener": "click", "action": [
                        {"key": 1, "type": "dom.popup", "selector": "#acc-grp-tpl", "aniIn": "fadeIn", "id": "<?= $this->id; ?>"},
                        {"key": 2, "type": "message.request", "uri": "<?= \phpOMS\Uri\UriFactory::build('{/base}/admin/account?filter=some&limit=10'); ?>", "method": "GET", "request_type": "json"},
                        {"key": 3, "type": "dom.table.append", "id": "acc-table", "aniIn": "fadeIn", "data": [], "bindings": {"id": "id", "name": "name/0"}, "position": -1},
                        {"key": 4, "type": "message.request", "uri": "<?= \phpOMS\Uri\UriFactory::build('{/base}/admin/account?filter=some&limit=10'); ?>", "method": "GET", "request_type": "json"},
                        {"key": 5, "type": "dom.table.append", "id": "grp-table", "aniIn": "fadeIn", "data": [], "bindings": {"id": "id", "name": "name/0"}, "position": -1}
                    ]
                }
            ]' formaction=""><i class="g-icon">book</i></button>
            <div class="advIpt wf-100" id="<?= $this->id; ?>">
                <input autocomplete="off" class="input" type="text" id="i<?= $this->id; ?>"
                    data-emptyAfter="true"
                    data-autocomplete="off"
                    data-src="api/media/find?search={!#mediaInput}">
                <div id="<?= $this->id; ?>-popup" class="popup" data-active="true">
                    <table class="default sticky">
                        <thead>
                            <tr>
                                <td>ID<i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                                <td>Name<i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                                <td>Extension<i class="sort-asc g-icon">expand_less</i><i class="sort-desc g-icon">expand_more</i>
                        <tbody>
                            <template id="<?= $this->id; ?>-rowElement" class="rowTemplate">
                                <tr tabindex="-1">
                                    <td data-tpl-text="/id" data-tpl-value="/id" data-value=""></td>
                                    <td data-tpl-text="/name" data-tpl-value="/name" data-value=""></td>
                                    <td data-tpl-text="/extension"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </span>
    </div>
    <div class="ipt-second"><button><?= $this->getHtml('Select', 'Media'); ?></button></div>
</div>
<div class="box" id="<?= $this->id; ?>-tags" data-limit="0" data-active="true">
    <template id="<?= $this->id; ?>-tagTemplate">
        <span class="tag red" data-tpl-value="/id" data-value="" data-uuid="" data-name="<?= $this->printHtml($this->name); ?>">
            <i class="g-icon close">close</i>
            <span style="display: none;" data-name="type_prefix" data-tpl-value="/type_prefix" data-value=""></span>
            <span data-tpl-text="/id" data-name="id" data-tpl-value="/id" data-value=""></span>
            <span data-tpl-text="/name/0" data-tpl-value="/name/0" data-value=""></span>
        </span>
    </template>
</div>
<tr><td>
        <input type="hidden" name="<?= $this->name; ?>-path" form="<?= $this->form; ?>" value="<?= $this->virtualPath; ?>">
        <input type="file" id="i<?= $this->form; ?>-upload" class="preview" name="<?= $this->name; ?>" form="<?= $this->form; ?>" multiple>
        <input form="<?= $this->form; ?>" type="hidden" name="media-list">