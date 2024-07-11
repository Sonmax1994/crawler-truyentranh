<?php

namespace App\Http\Controllers;

use App\Services\ChapterServices;
use App\Services\ComicServices;

abstract class Controller
{
    protected $comicServices;
    protected $chapterServices;

    protected $domainThumbApi;
    protected $thumbApiUrl;
    protected $domainChapterAPI;

    protected $cacheSeconds = 60 * 60;

    public function __construct()
    {
        $this->domainThumbApi = config('comic.thumb_domain_api');
        $this->thumbApiUrl = $this->domainThumbApi . 'uploads/comics/';
        $this->domainChapterAPI = config('comic.chapter_domain_api');
        $this->comicServices = new ComicServices();
        $this->chapterServices = new ChapterServices();
    }

    /**
     * @param $url
     * @param $queryParams
     * @return string
     */
    public function generateCacheKey($url, $queryParams)
    {
        ksort($queryParams);
        $queryString = http_build_query($queryParams);
        return md5($url . '?' . $queryString);
    }

    /**
     * @param array $data
     * @param bool $status
     * @param string $message
     * @param array $params
     * @return \Illuminate\Http\JsonResponse
     */
    public function commonResponse($data = [], $status = true, $message = "", $params = [])
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'params' => $params
        ]);
    }

    /**
     * @param $paginate
     * @param $dataItems
     * @param $params
     * @return array
     */
    public function customPaginateResponse($paginate, $dataItems = [], $params = [])
    {
        $customData = [
            'current_page' => $paginate->currentPage(),
            'data' => !empty($dataItems) ? $dataItems : $paginate->items(),
            'first_page_url' => $paginate->url(1),
            'from' => $paginate->firstItem(),
            'last_page' => $paginate->lastPage(),
            'last_page_url' => $paginate->url($paginate->lastPage()),
            'links' => $paginate->linkCollection(),
            'next_page_url' => $paginate->nextPageUrl(),
            'path' => $paginate->path(),
            'per_page' => $paginate->perPage(),
            'prev_page_url' => $paginate->previousPageUrl(),
            'to' => $paginate->lastItem(),
            'total' => $paginate->total(),
        ];
        return array_merge($params, $customData);
    }
}
