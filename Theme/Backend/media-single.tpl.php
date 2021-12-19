<?php
/**
 * Orange Management
 *
 * PHP Version 8.0
 *
 * @package   Modules\Media
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://orange-management.org
 */
declare(strict_types=1);

use \phpOMS\Uri\UriFactory;
use phpOMS\Utils\Converter\FileSizeType;

include __DIR__ . '/template-functions.php';

/** @var \Modules\Media\Views\MediaView $this */
/** @var \Modules\Media\Models\Media $media */
$media = $this->getData('media');
$view  = $this->getData('view');

/** @var \Modules\Tag\Models\Tag[] $tag */
$tags = $media->getTags();

/** @var \phpOMS\Message\Http\HttpRequest $this->request */

echo $this->getData('nav')->render();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <?php if ($this->request->getData('path') !== null) : ?>
                <a tabindex="0" class="button" href="<?= UriFactory::build('{/prefix}media/list?path=' . ($media->getId() === 0 ? $media->getVirtualPath() : '{?path}')); ?>"><?= $this->getHtml('Back'); ?></a>
            <?php else: ?>
                <a tabindex="0" class="button" href="<?= $this->request->getReferer() !== '' ? $this->request->getReferer() : UriFactory::build('{/prefix}media/list'); ?>"><?= $this->getHtml('Back'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <section class="portlet">
            <div class="portlet-head"><?= $this->printHtml($media->name); ?></div>
            <div class="portlet-body">
                <table class="list w-100">
                    <tbody>
                        <tr><td><?= $this->getHtml('Name'); ?><td class="wf-100"><?= $this->printHtml($media->name); ?>
                        <tr><td><?= $this->getHtml('Size'); ?><td class="wf-100"><?php
                            $size = FileSizeType::autoFormat($media->size);
                            echo $this->printHtml(\number_format($size[0], 1, '.', ',') . $size[1]); ?>
                        <tr><td><?= $this->getHtml('Created'); ?><td><?= $this->printHtml($media->createdAt->format('Y-m-d')); ?>
                        <tr><td><?= $this->getHtml('Creator'); ?><td><a href="<?= UriFactory::build('{/prefix}profile/single?for=' . $media->createdBy->getId()); ?>"><?= $this->printHtml(
                            \ltrim($media->createdBy->name2 . ', ' . $media->createdBy->name1, ', ')
                        ); ?></a>
                        <tr><td><?= $this->getHtml('Tags'); ?><td>
                            <?php foreach ($tags as $tag) : ?>
                                <span class="tag" style="background: <?= $this->printHtml($tag->color); ?>"><?= $tag->icon !== null ? '<i class="' . $this->printHtml($tag->icon ?? '') . '"></i>' : ''; ?><?= $this->printHtml($tag->getL11n()); ?></span>
                            <?php endforeach; ?>
                        <tr><td colspan="2"><?= $this->getHtml('Description'); ?>
                        <tr><td colspan="2"><?= $media->description; ?>
                </table>
            </div>
            <?php
            $path = $this->filePathFunction($media, $this->request->getData('sub') ?? '');
            if ($this->isTextFile($media, $path)) : ?>
            <div id="iMediaFileUpdate" class="portlet-foot"
                data-update-content="#mediaFile .portlet-body"
                data-update-element="#mediaFile .textContent"
                data-update-tpl="#iMediaUpdateTpl"
                data-tag="form"
                data-method="POST"
                data-uri="<?= UriFactory::build('{/api}media?{?}&csrf={$CSRF}'); ?>">
                <button class="save hidden"><?= $this->getHtml('Save', '0', '0'); ?></button>
                <button class="cancel hidden"><?= $this->getHtml('Cancel', '0', '0'); ?></button>
                <button class="update"><?= $this->getHtml('Edit', '0', '0'); ?></button>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="row" style="height: calc(100% - 85px);">
    <div class="col-xs-12">
        <?= $view->render($media); ?>
    </div>
</div>
