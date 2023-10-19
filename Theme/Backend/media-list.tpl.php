<?php
/**
 * Jingga
 *
 * PHP Version 8.1
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
use phpOMS\Utils\Converter\FileSizeType;

include __DIR__ . '/template-functions.php';

/**
 * @var \phpOMS\Views\View $this
 */
$mediaPath = \urldecode($this->getData('path') ?? '/');

/** @var \Modules\Media\Models\Media[] $media */
$media = $this->data['media'] ?? [];

/** @var \Modules\Admin\Models\Account $account */
$account    = $this->data['account'];
$accountDir = $account->id . ' ' . $account->login;

$previous = empty($media) ? '{/base}/media/list' : '{/base}/media/list?{?}&id=' . \reset($media)->id . '&ptype=p';
$next     = empty($media) ? '{/base}/media/list' : '{/base}/media/list?{?}&id=' . \end($media)->id . '&ptype=n';
?>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/base}/media/upload?path={?path}'); ?>">
                <?= $this->getHtml('Upload'); ?>
            </a>
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/base}/media/file/create?path={?path}'); ?>">
                <?= $this->getHtml('CreateFile'); ?>
            </a>
            <a tabindex="0" class="button" href="<?= UriFactory::build('{/base}/media/collection/create?path={?path}'); ?>">
                <?= $this->getHtml('CreateCollection'); ?>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <ul class="crumbs-2">
                <li data-href="<?= $uri = UriFactory::build('{/base}/media/list?path=/Accounts/' . $accountDir); ?>">
                    <a href="<?= $uri; ?>"><i class="g-icon">home</i></a>
                <li data-href="<?= $uri = UriFactory::build('{/base}/media/list?path=/'); ?>">
                    <a href="<?= $uri; ?>">/</a>
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

                        $url = UriFactory::build('{/base}/media/list?path=' . $subPath);
                ?>
                <li data-href="<?= $url; ?>"<?= $i === $length - 1 ? 'class="active"' : ''; ?>>
                    <a href="<?= $url; ?>"><?= $this->printHtml($paths[$i]); ?></a>
                <?php endfor; ?>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="portlet">
            <div class="portlet-head">
                <?= $this->getHtml('Media'); ?><i class="g-icon end-xs download btn">download</i>
            </div>
            <div class="slider">
            <table id="iMediaList" class="default sticky">
                <thead>
                <tr>
                    <td><label class="checkbox" for="iMediaSelect-">
                            <input type="checkbox" id="iMediaSelect-" name="mediaselect">
                            <span class="checkmark"></span>
                        </label>
                    <td>
                    <td class="wf-100"><?= $this->getHtml('Name'); ?>
                        <label for="iMediaList-sort-1">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-1">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="iMediaList-sort-2">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-2">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td class="wf-100"><?= $this->getHtml('Tag'); ?>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td><?= $this->getHtml('Type'); ?>
                        <label for="iMediaList-sort-3">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-3">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="iMediaList-sort-4">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-4">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td><?= $this->getHtml('Size'); ?>
                        <label for="iMediaList-sort-5">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-5">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="iMediaList-sort-6">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-6">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td><?= $this->getHtml('Creator'); ?>
                        <label for="iMediaList-sort-7">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-7">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="iMediaList-sort-8">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-8">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td><?= $this->getHtml('Created'); ?>
                        <label for="iMediaList-sort-9">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-9">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="iMediaList-sort-10">
                            <input type="radio" name="iMediaList-sort" id="iMediaList-sort-10">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                <tbody>
                    <?php if (!empty($parentPath)) :
                        $url = UriFactory::build('{/base}/media/list?path=' . $parentPath);
                    ?>
                        <tr tabindex="0" data-href="<?= $url; ?>">
                            <td>
                            <td data-label="<?= $this->getHtml('Type'); ?>">
                                <a href="<?= $url; ?>"><i class="g-icon">folder_open</i></a>
                            <td data-label="<?= $this->getHtml('Name'); ?>">
                                <a href="<?= $url; ?>">..
                            </a>
                            <td>
                            <td>
                            <td>
                            <td>
                            <td>
                    <?php endif; ?>
                    <?php $count = 0;
                        foreach ($media as $key => $value) :
                            ++$count;

                            $url = $value->extension === 'collection'
                                ? UriFactory::build('{/base}/media/list?path=' . \rtrim($value->getVirtualPath(), '/') . '/' . $value->name)
                                : UriFactory::build('{/base}/media/single?id=' . $value->id
                                    . '&path={?path}' . (
                                            $value->id === 0
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
                        <td data-label="<?= $this->getHtml('Type'); ?>">
                            <a href="<?= $url; ?>"><i class="g-icon"><?= $this->printHtml($icon); ?></i></a>
                        <td data-label="<?= $this->getHtml('Name'); ?>">
                            <a href="<?= $url; ?>"><?= $this->printHtml($value->name); ?></a>
                        <td data-label="<?= $this->getHtml('Tag'); ?>"><?php $tags = $value->getTags(); foreach ($tags as $tag) : ?>
                            <a href="<?= $url; ?>">
                                <span class="tag" style="background: <?= $this->printHtml($tag->color); ?>">
                                    <?= empty($tag->icon) ? '' : '<i class="' . $this->printHtml($tag->icon) . '"></i>'; ?>
                                    <?= $this->printHtml($tag->getL11n()); ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        <td data-label="<?= $this->getHtml('Extension'); ?>">
                            <a href="<?= $url; ?>"><?= $this->printHtml($value->extension); ?></a>
                        <td data-label="<?= $this->getHtml('Size'); ?>"><a href="<?= $url; ?>"><?php
                            $size = FileSizeType::autoFormat($value->size);
                            echo $this->printHtml($value->extension !== 'collection' ? \number_format($size[0], 1, '.', ',') . $size[1] : ''); ?></a>
                        <td data-label="<?= $this->getHtml('Creator'); ?>">
                            <a class="content" href="<?= UriFactory::build('{/base}/profile/single?{?}&for=' . $value->createdBy->id); ?>">
                                <?= $this->printHtml($this->renderUserName(
                                    '%3$s %2$s %1$s',
                                    [$value->createdBy->name1, $value->createdBy->name2, $value->createdBy->name3, $value->createdBy->login ?? '']
                                )); ?>
                            </a>
                        <td data-label="<?= $this->getHtml('Created'); ?>">
                            <a href="<?= $url; ?>"><?= $this->printHtml($value->createdAt->format('Y-m-d')); ?></a>
                        <?php endforeach; ?>
                    <?php if ($count === 0) : ?>
                        <tr><td colspan="8" class="empty"><?= $this->getHtml('Empty', '0', '0'); ?>
                    <?php endif; ?>
            </table>
            </div>
            <div class="portlet-foot">
                <a tabindex="0" class="button" href="<?= UriFactory::build($previous); ?>"><?= $this->getHtml('Previous', '0', '0'); ?></a>
                <a tabindex="0" class="button" href="<?= UriFactory::build($next); ?>"><?= $this->getHtml('Next', '0', '0'); ?></a>
                <a tabindex="0" class="button floatRight" href="<?= UriFactory::build('api/media/download'); ?>">
                    <?= $this->getHtml('Download'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
