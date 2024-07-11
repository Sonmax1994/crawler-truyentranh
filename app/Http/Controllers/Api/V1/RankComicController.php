<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ComicDTO;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Enums\RankComicType;

class RankComicController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $data = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($inputs) {
                $comics    = $this->comicServices->listComicTopView($inputs);
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
