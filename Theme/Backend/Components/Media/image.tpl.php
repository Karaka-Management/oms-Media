<?php declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div class="h-overflow centerText">
            <img style="max-width: 100%" src="<?= $this->media->getId() !== 0
                    ? UriFactory::build('{/api}media/export?id=' . $this->media->getId())
                    : UriFactory::build('{/api}media/export?path=' . \urlencode($this->media->getPath()));
                ?>" alt="<?= $this->printHtml($this->media->name); ?>">
        </div>
    </div>
</section>