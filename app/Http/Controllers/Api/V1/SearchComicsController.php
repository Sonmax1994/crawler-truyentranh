<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ComicDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SearchComicsController extends Controller
{

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            if (config('comic.mock_api_enable')) {
                return File::json(config_path('mock_json/search_comic.json'));
            }

            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $data = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($inputs) {
                $comics = $this->comicServices->searchComic($inputs);
                $comicDTOs = $comics->getCollection()->map(function ($comic) {
                    return new ComicDTO($comic);
                });

                return $this->customPaginateResponse($comics, $comicDTOs);
            });

            return $this->commonResponse($data, 'success', 'Success', $inputs);
        } catch (\Exception $e) {
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }

}
