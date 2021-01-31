<?php

use phpOMS\Uri\UriFactory;
?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <div class="h-overflow centerText">
            <img style="max-width: 100%" src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->getId()); ?>" alt="<?= $this->printHtml($this->media->name); ?>">
        </div>
    </div>
</section>