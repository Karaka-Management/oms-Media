<!DOCTYPE html>
<style>html, body, iframe { margin: 0; padding: 0; border: 0; }</style>
<pre><?php
    $json = $this->getFileContent(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());
    echo \json_encode(\json_decode($json), \JSON_PRETTY_PRINT);
?></pre>