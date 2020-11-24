<?php declare(strict_types=1);
use phpOMS\System\File\FileUtils;
use phpOMS\Uri\UriFactory;

include __DIR__ . '/../../template-functions.php';

?>
<div class="portlet">
    <div class="portlet-head"><?= $this->getHtml('Media', 'Media'); ?><i class="fa fa-download floatRight download btn"></i></div>
    <table class="default">
        <thead>
            <td>
            <td class="wf-100"><?= $this->getHtml('Name', 'Media'); ?>
            <td><?= $this->getHtml('Type', 'Media'); ?>
            <td><?= $this->getHtml('Size', 'Media'); ?>
            <td><?= $this->getHtml('Creator', 'Media'); ?>
            <td><?= $this->getHtml('Created', 'Media'); ?>
        <tbody>
            <?php $count = 0; foreach ($this->media as $key => $value) : ++$count;
                $url     = UriFactory::build('{/prefix}media/single?{?}&id=' . $value->getId());

                $icon          = '';
                $extensionType = FileUtils::getExtensionType($value->extension);
                $icon          = $fileIconFunction($extensionType);
        ?>
        <tr data-href="<?= $url; ?>">
            <td data-label="<?= $this->getHtml('Type'); ?>"><a href="<?= $url; ?>"><i class="fa fa-<?= $this->printHtml($icon); ?>"></i></a>
            <td data-label="<?= $this->getHtml('Name'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->name); ?></a>
            <td data-label="<?= $this->getHtml('Extension'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->extension); ?></a>
            <td data-label="<?= $this->getHtml('Size'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->size); ?></a>
            <td data-label="<?= $this->getHtml('Creator'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->createdBy->name1); ?></a>
            <td data-label="<?= $this->getHtml('Created'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->createdAt->format('Y-m-d H:i:s')); ?></a>
        <?php endforeach; ?>
        <?php if ($count === 0) : ?>
        <tr><td colspan="6" class="empty"><?= $this->getHtml('Empty', '0', '0'); ?>
        <?php endif; ?>
    </table>
    <div class="portlet-foot"></div>
</div>