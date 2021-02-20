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

use phpOMS\System\File\FileUtils;
use phpOMS\Uri\UriFactory;
use phpOMS\Utils\Converter\FileSizeType;

include __DIR__ . '/template-functions.php';

/**
 * @var \phpOMS\Views\View $this
 * @var string             $parent
 */
$mediaPath = \urldecode($this->getData('path') ?? '/');

/**
 * @var \Modules\Media\Models\Media[] $media
 */
$media = $this->getData('media');

$previous = empty($media) ? '{/prefix}media/list' : '{/prefix}media/list?{?}&id=' . \reset($media)->getId() . '&ptype=p';
$next     = empty($media) ? '{/prefix}media/list' : '{/prefix}media/list?{?}&id=' . \end($media)->getId() . '&ptype=n';
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/prefix}media/upload?path={?path}'); ?>"><?= $this->getHtml('Upload'); ?></a>
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/prefix}media/file/create?path={?path}'); ?>"><?= $this->getHtml('CreateFile'); ?></a>
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/prefix}media/collection/create?path={?path}'); ?>"><?= $this->getHtml('CreateCollection'); ?></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <ul class="crumbs-2">
                <li data-href="<?= UriFactory::build('{/prefix}media/list?path=/'); ?>"><a href="<?= UriFactory::build('{/prefix}media/list?path=/'); ?>">/</a></li>
                <?php
                    $subPath = '';
                    $paths   = \explode('/', \ltrim($mediaPath, '/'));
                    $length  = \count($paths);

                    for ($i = 0; $i < $length; ++$i) :
                        if ($paths[$i] === '') {
                            continue;
                        }

                        $subPath .= '/' . $paths[$i];

                        $url = UriFactory::build('{/prefix}media/list?path=' . $subPath);
                ?>
                    <li data-href="<?= $url; ?>"<?= $i === $length - 1 ? 'class="active"' : ''; ?>><a href="<?= $url; ?>"><?= $this->printHtml($paths[$i]); ?></a></li>
                <?php endfor; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="portlet">
            <div class="portlet-head"><?= $this->getHtml('Media'); ?><i class="fa fa-download floatRight download btn"></i></div>
            <table id="mediaList" class="default">
                <thead>
                <tr>
                    <td>
                    <td class="wf-100"><?= $this->getHtml('Name'); ?>
                    <td><?= $this->getHtml('Type'); ?>
                    <td><?= $this->getHtml('Size'); ?>
                    <td><?= $this->getHtml('Creator'); ?>
                    <td><?= $this->getHtml('Created'); ?>
                <tbody>
                    <?php $count = 0;
                        foreach ($media as $key => $value) :
                            ++$count;

                            $url = $value->extension === 'collection'
                                ? UriFactory::build('{/prefix}media/list?path=' . \rtrim($value->getVirtualPath(), '/') . '/' . $value->name)
                                : UriFactory::build('{/prefix}media/single?id=' . $value->getId()
                                    . '&path={?path}' . (
                                            $value->getId() === 0
                                                ? '/' . $value->name
                                                : ''
                                        )
                                );

                            $icon = $fileIconFunction(FileUtils::getExtensionType($value->extension));
                        ?>
                    <tr tabindex="0" data-href="<?= $url; ?>">
                        <td data-label="<?= $this->getHtml('Type'); ?>"><a href="<?= $url; ?>"><i class="fa fa-<?= $this->printHtml($icon); ?>"></i></a>
                        <td data-label="<?= $this->getHtml('Name'); ?>"><a href="<?= $url; ?>">
                            <?= $this->printHtml($value->name); ?>
                            </a>
                        <td data-label="<?= $this->getHtml('Extension'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->extension); ?></a>
                        <td data-label="<?= $this->getHtml('Size'); ?>"><a href="<?= $url; ?>"><?php
                            $size = FileSizeType::autoFormat($value->size);
                            echo $this->printHtml($value->extension !== 'collection' ? \number_format($size[0], 1, '.', ',') . $size[1] : ''); ?></a>
                        <td data-label="<?= $this->getHtml('Creator'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->createdBy->name1); ?></a>
                        <td data-label="<?= $this->getHtml('Created'); ?>"><a href="<?= $url; ?>"><?= $this->printHtml($value->createdAt->format('Y-m-d H:i:s')); ?></a>
                        <?php endforeach; ?>
                    <?php if ($count === 0) : ?>
                        <tr><td colspan="6" class="empty"><?= $this->getHtml('Empty', '0', '0'); ?>
                    <?php endif; ?>
            </table>
            <div class="portlet-foot">
                <a tabindex="0" class="button" href="<?= UriFactory::build($previous); ?>"><?= $this->getHtml('Previous', '0', '0'); ?></a>
                <a tabindex="0" class="button" href="<?= UriFactory::build($next); ?>"><?= $this->getHtml('Next', '0', '0'); ?></a>
            </div>
        </div>
    </div>
</div>
