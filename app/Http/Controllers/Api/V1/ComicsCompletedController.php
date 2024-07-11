<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ComicDTO;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ComicsCompletedController extends Controller
{
    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            if (config('comic.mock_api_enable')) {
                return File::json(config_path('mock_json/comics_completed.json'));
            }

            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $comics = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($inputs) {
                $comics = $this->comicServices->getComicsCompleted($inputs);

                $comicDTOs = $comics->getCollection()->map(function ($comic) {
                    return new ComicDTO($comic);
                });

                return $this->customPaginateResponse($comics, $comicDTOs, []);
            });

            return $this->commonResponse($comics, 'success', 'Success', $inputs);
        } catch (Exception $e) {
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }

}
