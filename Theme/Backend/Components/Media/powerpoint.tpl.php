<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Template
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../../../../phpOMS/Autoloader.php';
use phpOMS\Autoloader;

Autoloader::addPath(__DIR__ . '/../../../../../../Resources/');

use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Writer\Html;

$reader       = IOFactory::createReader('PowerPoint2007');
$presentation = $reader->load(($this->media->isAbsolute ? '' : __DIR__ . '/../../../../../../') . $this->media->getPath());

$writer = new Html($presentation);
?>
<section id="mediaFile" class="portlet">
    <div class="portlet-body">
        <?php $writer->save('php://output'); ?>
    </div>
</section>
