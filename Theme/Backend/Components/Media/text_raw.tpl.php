<pre class="textContent" data-tpl-text="/media/content" data-tpl-value="/media/content"><?= $this->printHtml(
    $this->getFileContent(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath())
); ?>