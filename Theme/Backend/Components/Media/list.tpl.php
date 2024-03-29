<?php
/**
 * Jingga
 *
 * PHP Version 8.2
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\System\File\FileUtils;
use phpOMS\Uri\UriFactory;

include __DIR__ . '/../../template-functions.php';

$previous = empty($this->media)
    ? '{%}'
    : '{%}?{?}&mpivot=' . \reset($this->media)->id . '&mptype=p';
$next = empty($this->media)
    ? '{%}'
    : '{%}?{?}&mpivot=' . \end($this->media)->id . '&mptype=n';
?>
<div class="portlet">
    <div class="portlet-head"><?= $this->getHtml('Media', 'Media'); ?><i class="g-icon download btn end-xs">download</i></div>
    <div class="slider">
    <table class="default sticky">
        <thead>
            <td>
            <td><?= $this->getHtml('Path', 'Media'); ?>
            <td class="wf-100"><?= $this->getHtml('Name', 'Media'); ?>
            <td><?= $this->getHtml('Type', 'Media'); ?>
            <td><?= $this->getHtml('Size', 'Media'); ?>
            <td><?= $this->getHtml('Creator', 'Media'); ?>
            <td><?= $this->getHtml('Created', 'Media'); ?>
        <tbody>
            <?php $count = 0; foreach ($this->media as $key => $value) : ++$count;
                $url     = UriFactory::build('{/base}/media/view?{?}&id=' . $value->id);

                $icon          = '';
                $extensionType = FileUtils::getExtensionType($value->extension);
                $icon          = $fileIconFunction($extensionType);
        ?>
        <tr data-href="<?= $url; ?>">
            <td data-label="<?= $this->getHtml('Type'); ?>"><a href="<?= $url; ?>"><i class="g-icon"><?= $this->printHtml($icon); ?></i></a>
            <td data-label="<?= $this->getHtml('Path'); ?>"><a class="content" href="<?= UriFactory::build('{/base}/media/list?{?}&path=' . $value->getVirtualPath()); ?>"><?= $this->printHtml($value->getVirtualPath()); ?></a>
            <td data-label="<?= $this->getHtml('Name'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->name); ?></a>
            <td data-label="<?= $this->getHtml('Extension'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->extension); ?></a>
            <td data-label="<?= $this->getHtml('Size'); ?>"><a href="<?= $url; ?>"><?= $value->size; ?></a>
            <td data-label="<?= $this->getHtml('Creator'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->createdBy->name1); ?></a>
            <td data-label="<?= $this->getHtml('Created'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->createdAt->format('Y-m-d')); ?></a>
        <?php endforeach; ?>
        <?php if ($count === 0) : ?>
            <tr><td colspan="7" class="empty"><?= $this->getHtml('Empty', '0', '0'); ?>
        <?php endif; ?>
    </table>
    </div>
    <!--
    <div class="portlet-foot">
        <a tabindex="0" class="button" href="<?= UriFactory::build($previous); ?>"><?= $this->getHtml('Previous', '0', '0'); ?></a>
        <a tabindex="0" class="button" href="<?= UriFactory::build($next); ?>"><?= $this->getHtml('Next', '0', '0'); ?></a>
    </div>
    -->
</div>