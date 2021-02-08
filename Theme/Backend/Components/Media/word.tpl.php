<?php declare(strict_types=1);

use phpOMS\Uri\UriFactory;

?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
    	<iframe src="<?= UriFactory::build('{/api}media/export?id=' . $this->media->getId()); ?>&type=html"></iframe>
    </div>
</section>
