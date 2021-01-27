<?php

// @todo: is this chunked/streamed output or bulk output
// if it is streamed it is not working because of ob_* in the actual response rendering
\fpassthru($fp);
