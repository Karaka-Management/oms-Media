<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <video width="100%" controls>
            <source src="<?= $this->media->getPath(); ?>" type="video/<?= $this->media->extension; ?>">
            Your browser does not support HTML video.
        </video>
    </div>
</section>