<?php declare(strict_types=1);

// @todo: is this chunked/streamed output or bulk output
// if it is streamed it is not working because of ob_* in the actual response rendering

$media = $this->getData('media');

$fp = \fopen(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'r');
\fpassthru($fp);
\fclose($fp);
