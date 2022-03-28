<?php
/**
 * Karaka
 *
 * PHP Version 8.0
 *
 * @package   Modules\Auditor
 * @copyright Dennis Eichhorn
 * @license   OMS License 1.0
 * @version   1.0.0
 * @link      https://karaka.app
 */
declare(strict_types=1);

use phpOMS\Uri\UriFactory;

/**
 * @var \phpOMS\Views\View                $this
 * @var \Modules\Media\Models\MediaType[] $types
 */
$types = $this->getData('types') ?? [];

echo $this->getData('nav')->render(); ?>

<div class="row">
    <div class="col-xs-12">
        <div class="portlet">
            <div class="portlet-head"><?= $this->getHtml('Types'); ?><i class="fa fa-download floatRight download btn"></i></div>
            <div class="slider">
            <table id="typeList" class="default sticky">
                <thead>
                <tr>
                    <td><?= $this->getHtml('ID', '0', '0'); ?>
                        <label for="typeList-sort-1">
                            <input type="radio" name="typeList-sort" id="typeList-sort-1">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="typeList-sort-2">
                            <input type="radio" name="typeList-sort" id="typeList-sort-2">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                    <td><?= $this->getHtml('keyword'); ?>
                        <label for="typeList-sort-3">
                            <input type="radio" name="typeList-sort" id="typeList-sort-3">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="typeList-sort-4">
                            <input type="radio" name="typeList-sort" id="typeList-sort-4">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                    <td><?= $this->getHtml('Name'); ?>
                        <label for="typeList-sort-5">
                            <input type="radio" name="typeList-sort" id="typeList-sort-5">
                            <i class="sort-asc fa fa-chevron-up"></i>
                        </label>
                        <label for="typeList-sort-6">
                            <input type="radio" name="typeList-sort" id="typeList-sort-6">
                            <i class="sort-desc fa fa-chevron-down"></i>
                        </label>
                        <label>
                            <i class="filter fa fa-filter"></i>
                        </label>
                <tbody>
                <?php $count = 0;
                foreach ($types as $key => $type) : ++$count;
                    $url = UriFactory::build('{/prefix}admin/module/settings?id=Media&type=' . $type->id); ?>
                    <tr tabindex="0" data-href="<?= $url; ?>">
                        <td><a href="<?= $url; ?>"><?= $type->getId(); ?></a>
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