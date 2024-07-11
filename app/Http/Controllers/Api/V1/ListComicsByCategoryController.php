<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ComicDTO;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ListComicsByCategoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            if (config('comic.mock_api_enable')) {
                return File::json(config_path('mock_json/list_comics_by_categories.json'));
            }
            $idsCategory = $request->ids_category ?? '';
            $idsCategory = array_filter(explode(',', $idsCategory));

            if (empty($idsCategory)) {
                throw new \Exception("ids category is not null.");
            }

            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $dataComics = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($idsCategory, $inputs) {
                $comics =  $this->comicServices->getListComicsByCate($idsCategory, $inputs);
                $comicDTOs = $comics->getCollection()->map(function ($comic) {
                    return new ComicDTO($comic);
                });

                return $this->customPaginateResponse($comics, $comicDTOs);
            });

            return $this->commonResponse($dataComics, 'success', 'Success', $inputs);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }

}
