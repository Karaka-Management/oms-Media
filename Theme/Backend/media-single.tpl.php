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

use \phpOMS\System\File\FileUtils;
use \phpOMS\System\File\Local\File;
use \phpOMS\Uri\UriFactory;
use phpOMS\Utils\IO\Csv\CsvSettings;
use phpOMS\Utils\Parser\Markdown\Markdown;

include __DIR__ . '/template-functions.php';

/** @var \Modules\Media\Models\Media $media */
$media = $this->getData('media');
$view = $this->getData('view');

/** @var \Modules\Media\Views\MediaView $this */
echo $this->getData('nav')->render();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <?php if ($this->request->getData('path') !== null) : ?>
                <a tabindex="0" class="button" href="<?= UriFactory::build('{/prefix}media/list?path=' . ($media->getId() === 0 ? $media->getVirtualPath() : '{?path}')); ?>"><?= $this->getHtml('Back'); ?></a>
            <?php else: ?>
                <a tabindex="0" class="button" href="<?= UriFactory::build('{/prefix}media/list'); ?>"><?= $this->getHtml('Back'); ?></a>
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
                        <tr><td><?= $this->getHtml('Size'); ?><td class="wf-100"><?= $media->size; ?>
                        <tr><td><?= $this->getHtml('Created'); ?><td><?= $this->printHtml($media->createdAt->format('Y-m-d')); ?>
                        <tr><td><?= $this->getHtml('Creator'); ?><td><a href="<?= UriFactory::build('{/prefix}profile/single?for=' . $media->createdBy->getId()); ?>"><?= $this->printHtml(
                                \ltrim($media->createdBy->name2 . ', ' . $media->createdBy->name1, ', ')
                            ); ?></a>
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

<!--
<div class="row">
    <?php if ($this->isCollectionFunction($media, $this->request->getData('sub') ?? '')) : ?>
    <div class="col-xs-12">
        <section class="portlet">
            <table class="default">
                <caption><?= $this->getHtml('Media'); ?><i class="fa fa-download floatRight download btn"></i></caption>
                <thead>
                <tr>
                    <td>
                    <td class="wf-100"><?= $this->getHtml('Name'); ?>
                    <td><?= $this->getHtml('Type'); ?>
                    <td><?= $this->getHtml('Size'); ?>
                    <td><?= $this->getHtml('Creator'); ?>
                    <td><?= $this->getHtml('Created'); ?>
                <tbody>
                    <?php
                        if (!\is_dir($media->isAbsolute ? $media->getPath() : __DIR__ . '/../../../../' . \ltrim($media->getPath(), '//'))
                            || $media->getPath() === ''
                        ) :
                            foreach ($media as $key => $value) :
                                $url  = UriFactory::build('{/prefix}media/single?{?}&id=' . $value->getId());
                                $icon = $fileIconFunction(FileUtils::getExtensionType($value->extension));
                        ?>
                        <tr data-href="<?= $url; ?>">
                            <td><a href="<?= $url; ?>"><i class="fa fa-<?= $this->printHtml($icon); ?>"></i></a>
                            <td><a href="<?= $url; ?>"><?= $this->printHtml($value->name); ?></a>
                            <td><a href="<?= $url; ?>"><?= $this->printHtml($value->extension); ?></a>
                            <td><a href="<?= $url; ?>"><?= $value->size; ?></a>
                            <td><a href="<?= $url; ?>"><?= $this->printHtml($value->createdBy->name1); ?></a>
                            <td><a href="<?= $url; ?>"><?= $this->printHtml($value->createdAt->format('Y-m-d H:i:s')); ?></a>
                    <?php endforeach; else : $path = $this->dirPathFunction($media, $this->request->getData('sub') ?? ''); ?>
                        <?php $list                = \phpOMS\System\File\Local\Directory::list($path);
                            foreach ($list as $key => $value) :
                                $url  = UriFactory::build('{/prefix}media/single?{?}&id=' . $media->getId() . '&sub=' . \substr($value, \strlen($media->getPath())));
                                $icon = $fileIconFunction(FileUtils::getExtensionType(!\is_dir($value) ? File::extension($value) : 'collection'));
                        ?>
                        <tr data-href="<?= $url; ?>">
                            <td><a href="<?= $url; ?>"><i class="fa fa-<?= $this->printHtml($icon); ?>"></i></a>
                            <td><a href="<?= $url; ?>"><?= \substr($value, \strlen($media->getPath())); ?></a>
                            <td><a href="<?= $url; ?>"><?= !\is_dir($value) ? File::extension($value) : 'collection'; ?></a>
                            <td><a href="<?= $url; ?>"><?= !\is_dir($value) ? File::size($value) : ''; ?></a>
                            <td><a href="<?= $url; ?>"><?= File::owner($value); ?></a>
                            <td><a href="<?= $url; ?>"><?= File::created($value)->format('Y-m-d'); ?></a>
                    <?php endforeach; endif; ?>
            </table>
        </section>
    </div>
    <?php else: ?>
    <div class="col-xs-12">
        <section id="mediaFile" class="portlet">
            <div class="portlet-body">
                <?php
                $path = $this->filePathFunction($media, $this->request->getData('sub') ?? '');

                if ($this->isImageFile($media, $path)) : ?>
                    <div class="h-overflow centerText">
                        <img style="max-width: 100%" src="<?= $media->getPath(); ?>" alt="<?= $this->printHtml($media->name); ?>">
                    </div>
                <?php elseif ($this->isTextFile($media, $path)) : ?>
                    <?php if (!\is_file(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath())) : ?>
                        <div class="centerText"><i class="fa fa-question fa-5x"></i></div>
                    <?php else : ?>
                        <template id="iMediaUpdateTpl">
                            <textarea class="textContent" form="iMediaFileUpdate" data-tpl-text="/media/content" data-tpl-value="/media/content" data-marker="tpl" name="content"></textarea>
                        </template>
                        <?php if ($media->extension === 'csv') :
                            $f = \fopen(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath(), 'r');

                            echo '<table class="default">';
                            $delim = CsvSettings::getFileDelimiter($f, 3);
                            while (($line = \fgetcsv($f, 0, $delim)) !== false) {
                                echo '<tr>';
                                foreach ($line as $cell) {
                                    echo '<td>' . \htmlspecialchars($cell);
                                }
                            }

                            \fclose($f);
                            echo '</table>';
                        elseif ($media->extension === 'md') : ?>
                            <article><?= Markdown::parse(
                                $this->getFileContent(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath())
                            ); ?></article>
                        <?php else : ?>
                            <pre class="textContent" data-tpl-text="/media/content" data-tpl-value="/media/content"><?= $this->printHtml(
                                $this->getFileContent(($media->isAbsolute ? '' : __DIR__ . '/../../../../') . $media->getPath())
                            ); ?></pre>
                        <?php endif; ?>
                    <?php endif; ?>
                 <?php elseif ($this->isVideoFile($media, $path)) : ?>
                    <video width="100%" controls>
                        <source src="<?= $media->getPath(); ?>" type="video/<?= $media->extension; ?>">
                        Your browser does not support HTML video.
                    </video>
                <?php elseif ($this->isAudioFile($media, $path)) : ?>
                    <audio width="100%" controls>
                        <source src="<?= $media->getPath(); ?>" type="audio/<?= $media->extension; ?>">
                        Your browser does not support HTML audio.
                    </audio>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>
</div>
-->