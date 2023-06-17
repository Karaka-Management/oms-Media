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

use \phpOMS\Uri\UriFactory;
use Modules\Media\Models\MediaClass;
use phpOMS\Utils\Converter\FileSizeType;

include __DIR__ . '/template-functions.php';

/** @var \Modules\Media\Views\MediaView $this */
/** @var \Modules\Media\Models\Media $media */
$media = $this->data['media'];
$view  = $this->data['view'];

/** @var \Modules\Tag\Models\Tag[] $tag */
$tags = $media->getTags();

/** @var \Modules\Admin\Models\Account $account */
$account    = $this->data['account'];
$accountDir = $account->id . ' ' . $account->login;

$mediaPath = \urldecode($media->getVirtualPath() ?? '/');

/** @var \phpOMS\Message\Http\HttpRequest $this->request */

echo $this->data['nav']->render();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <?php if ($this->request->getData('path') !== null) : ?>
                <a tabindex="0" class="button" href="<?= UriFactory::build('{/app}/media/list?path=' . ($media->id === 0 ? $media->getVirtualPath() : '{?path}')); ?>"><?= $this->getHtml('Back'); ?></a>
            <?php else: ?>
                <a tabindex="0" class="button" href="<?= $this->request->getReferer() !== '' ? $this->request->getReferer() : UriFactory::build('{/app}/media/list'); ?>"><?= $this->getHtml('Back'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <ul class="crumbs-2">
                <li data-href="<?= UriFactory::build('{/app}/media/list?path=/Accounts/' . $accountDir); ?>"><a href="<?= UriFactory::build('{/app}/media/list?path=/Accounts/' . $accountDir); ?>"><i class="fa fa-home"></i></a>
                <li data-href="<?= UriFactory::build('{/app}/media/list?path=/'); ?>"><a href="<?= UriFactory::build('{/app}/media/list?path=/'); ?>">/</a></li>
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

                        $url = UriFactory::build('{/app}/media/list?path=' . $subPath);
                ?>
                    <li data-href="<?= $url; ?>"<?= $i === $length - 1 ? 'class="active"' : ''; ?>><a href="<?= $url; ?>"><?= $this->printHtml($paths[$i]); ?></a></li>
                <?php endfor; ?>
            </ul>
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
                        <tr><td><?= $this->getHtml('Creator'); ?><td><a href="<?= UriFactory::build('{/app}/profile/single?for=' . $media->createdBy->id); ?>"><?= $this->printHtml(
                            \ltrim($media->createdBy->name2 . ', ' . $media->createdBy->name1, ', ')
                        ); ?></a>
                        <tr><td><?= $this->getHtml('Tags'); ?><td>
                            <?php foreach ($tags as $tag) : ?>
                                <span class="tag" style="background: <?= $this->printHtml($tag->color); ?>"><?= !empty($tag->icon) ? '<i class="' . $this->printHtml($tag->icon) . '"></i>' : ''; ?><?= $this->printHtml($tag->getL11n()); ?></span>
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

<?php
$media = $media->class === MediaClass::REFERENCE ? $media->source : $media;
?>

<div class="row col-simple">
    <div class="col-xs-12 col-simple">
        <?= $view->render($media); ?>
    </div>
</div>
