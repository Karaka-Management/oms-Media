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
$media   = $this->getData('media');
$account = $this->getData('account');

$accountDir = $account->getId() . ' ' . $account->login;

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
                <li data-href="<?= UriFactory::build('{/prefix}media/list?path=/Accounts/' . $accountDir); ?>"><a href="<?= UriFactory::build('{/prefix}media/list?path=/Accounts/' . $accountDir); ?>"><i class="fa fa-home"></i></a>
                <li data-href="<?= UriFactory::build('{/prefix}media/list?path=/'); ?>"><a href="<?= UriFactory::build('{/prefix}media/list?path=/'); ?>">/</a></li>
                <?php
                    $subPath    = '';
                    $paths      = \explode('/', \ltrim($mediaPath, '/'));
                    $length     = \count($paths);
                    $parentPath = '';

                    for ($i = 0; $i < $length; ++$i) :
                        if ($paths[$i] === '') {
                            continue;
                        }

                        if ($i === $length - 1) {
                            $parentPath = $subPath === '' ? '/' : $subPath;
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
            <table id="iMediaList" class="default">
                <thead>
                <tr>
                    <td><label class="checkbox" for="iMediaSelect-0">
                            <input type="checkbox" id="iMediaSelect-0" name="mediaselect">
                            <span class="checkmark"></span>
                        </label>
                    <td>
                    <td class="wf-100"><?= $this->getHtml('Name'); ?>
                        <label for="iMediaList-sort-1">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-1">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="iMediaList-sort-2">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-2">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                    <td><?= $this->getHtml('Type'); ?>
                        <label for="iMediaList-sort-3">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-3">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="iMediaList-sort-4">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-4">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                    <td><?= $this->getHtml('Size'); ?>
                        <label for="iMediaList-sort-5">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-5">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="iMediaList-sort-6">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-6">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                    <td><?= $this->getHtml('Creator'); ?>
                        <label for="iMediaList-sort-7">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-7">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="iMediaList-sort-8">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-8">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                    <td><?= $this->getHtml('Created'); ?>
                        <label for="iMediaList-sort-9">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-9">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="iMediaList-sort-10">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-10">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                <tbody>
                    <?php if (!empty($parentPath)) : $url = UriFactory::build('{/prefix}media/list?path=' . $parentPath); ?>
                        <tr tabindex="0" data-href="<?= $url; ?>">
                            <td>
                            <td data-label="<?= $this->getHtml('Type'); ?>"><a href="<?= $url; ?>"><i class="fa fa-folder-open-o"></i></a>
                            <td data-label="<?= $this->getHtml('Name'); ?>"><a href="<?= $url; ?>">..
                            </a>
                            <td>
                            <td>
                            <td>
                            <td>
                    <?php endif; ?>
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
                        <td><label class="checkbox" for="iMediaSelect-<?= $key; ?>">
                                    <input type="checkbox" id="iMediaSelect-<?= $key; ?>" name="mediaselect">
                                    <span class="checkmark"></span>
                                </label>
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
                <a tabindex="0" class="button floatRight" href="<?= UriFactory::build('{/prefix}api/media/download'); ?>"><?= $this->getHtml('Download', '0', '0'); ?></a>
            </div>
        </div>
    </div>
</div>
