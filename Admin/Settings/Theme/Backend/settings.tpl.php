<?php
/**
 * Jingga
 *
 * PHP Version 8.1
 *
 * @package   Modules\Auditor
 * @copyright Dennis Eichhorn
 * @license   OMS License 2.0
 * @version   1.0.0
 * @link      https://jingga.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

/**
 * @var \phpOMS\Views\View                $this
 * @var \Modules\Media\Models\MediaType[] $types
 */
$types = $this->data['types'] ?? [];

echo $this->data['nav']->render(); ?>

<div class="row">
    <div class="col-xs-12">
        <div class="portlet">
            <div class="portlet-head"><?= $this->getHtml('Types'); ?><i class="g-icon download btn end-xs">download</i></div>
            <div class="slider">
            <table id="typeList" class="default sticky">
                <thead>
                <tr>
                    <td><?= $this->getHtml('ID', '0', '0'); ?>
                        <label for="typeList-sort-1">
                            <input type="radio" name="typeList-sort" id="typeList-sort-1">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="typeList-sort-2">
                            <input type="radio" name="typeList-sort" id="typeList-sort-2">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td><?= $this->getHtml('keyword'); ?>
                        <label for="typeList-sort-3">
                            <input type="radio" name="typeList-sort" id="typeList-sort-3">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="typeList-sort-4">
                            <input type="radio" name="typeList-sort" id="typeList-sort-4">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                    <td><?= $this->getHtml('Name'); ?>
                        <label for="typeList-sort-5">
                            <input type="radio" name="typeList-sort" id="typeList-sort-5">
                            <i class="sort-asc g-icon">expand_less</i>
                        </label>
                        <label for="typeList-sort-6">
                            <input type="radio" name="typeList-sort" id="typeList-sort-6">
                            <i class="sort-desc g-icon">expand_more</i>
                        </label>
                        <label>
                            <i class="filter g-icon">filter_alt</i>
                        </label>
                <tbody>
                <?php $count = 0;
                foreach ($types as $key => $type) : ++$count;
                    $url = UriFactory::build('{/base}/admin/module/settings?id=Media&type=' . $type->id); ?>
                    <tr tabindex="0" data-href="<?= $url; ?>">
                        <td><a href="<?= $url; ?>"><?= $type->id; ?></a>
                        <td><a href="<?= $url; ?>"><?= $this->printHtml($type->name); ?></a>
                        <td><a href="<?= $url; ?>"><?= $this->printHtml($type->getL11n()); ?></a>
                <?php endforeach; ?>
                <?php if ($count === 0) : ?>
                    <tr><td colspan="8" class="empty"><?= $this->getHtml('Empty', '0', '0'); ?>
                <?php endif; ?>
            </table>
            </div>
        </div>
    </div>
</div>
