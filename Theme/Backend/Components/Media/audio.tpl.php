<?php declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <audio width="100%" controls>
            <source src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->getId()); ?>" type="audio/<?= $this->media->extension; ?>">
            Your browser does not support HTML audio.
        </audio>
    </div>
</section>