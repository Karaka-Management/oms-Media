<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <audio width="100%" controls>
            <source src="<?= $this->media->getPath(); ?>" type="audio/<?= $this->media->extension; ?>">
            Your browser does not support HTML audio.
        </audio>
    </div>
</section>