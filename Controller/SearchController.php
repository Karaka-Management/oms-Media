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

namespace Modules\Media\Controller;

use Modules\Media\Models\MediaMapper;
use phpOMS\DataStorage\Database\Query\OrderType;
use phpOMS\Message\RequestAbstract;
use phpOMS\Message\ResponseAbstract;
use phpOMS\System\MimeType;

/**
 * Search class.
 *
 * @package Modules\Media
 * @license OMS License 2.0
 * @link    https://jingga.app
 * @since   1.0.0
 */
final class SearchController extends Controller
{
    /**
     * Api method to search for tags
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @since 1.0.0
     */
    public function searchTag(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        $search = $request->getDataString('search') ?? '';

        $searchIdStartPos = \stripos($search, ':');
        $patternStartPos  = $searchIdStartPos === false
            ? -1
            : \stripos($search, ' ', $searchIdStartPos);

        $pattern = \substr($search, $patternStartPos + 1);

        /** @var \Modules\Media\Models\Media[] $media */
        $media = MediaMapper::getAll()
            ->with('tags')
            ->with('tags/title')
            ->where('tags/title/language', $response->header->l11n->language)
            ->where('tags/title/content', $pattern)
            ->sort('createdAt', OrderType::DESC)
            ->limit(8)
            ->execute();

        $results = [];
        foreach ($media as $file) {
            $results[] = [
                'title'     => $file->name . ' (' . $file->extension . ')',
                'summary'   => '',
                'link'      => '{/base}/media/view?id=' . $file->id,
                'account'   => '',
                'createdAt' => $file->createdAt,
                'image'     => '',
                'tags'      => $file->tags,
                'type'      => 'list_links',
                'module'    => 'Media',
            ];
        }

        $response->header->set('Content-Type', MimeType::M_JSON . '; charset=utf-8', true);
        $response->add($request->uri->__toString(), $results);
    }

    /**
     * Api method to search for tags
     *
     * @param RequestAbstract  $request  Request
     * @param ResponseAbstract $response Response
     * @param array            $data     Generic data
     *
     * @return void
     *
     * @api
     *
     * @todo Implement a decent full text search for files/variables which finds texts that are similar
     *      (e.g. similar spelling, only some words in between, maybe different word order, etc.)
     *      Solution: Elasticsearch
     *      https://github.com/Karaka-Management/Karaka/issues/160
     *
     * @since 1.0.0
     */
    public function searchGeneral(RequestAbstract $request, ResponseAbstract $response, array $data = []) : void
    {
        /** @var \Modules\Media\Models\Media[] $media */
        $media = MediaMapper::getAll()
            ->with('tags')
            ->with('tags/title')
            ->where('name', '%' . ($request->getDataString('search') ?? '') . '%', 'LIKE')
            ->where('tags/title/language', $response->header->l11n->language)
            ->sort('createdAt', OrderType::DESC)
            ->limit(8)
            ->execute();

        $results = [];
        foreach ($media as $file) {
            $results[] = [
                'title'     => $file->name . ' (' . $file->extension . ')',
                'summary'   => '',
                'link'      => '{/base}/media/view?id=' . $file->id,
                'account'   => '',
                'createdAt' => $file->createdAt,
                'image'     => '',
                'tags'      => $file->tags,
                'type'      => 'list_links',
                'module'    => 'Media',
            ];
        }

        $response->header->set('Content-Type', MimeType::M_JSON . '; charset=utf-8', true);
        $response->add($request->uri->__toString(), $results);
    }
}
