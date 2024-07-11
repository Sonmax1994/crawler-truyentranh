<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ComicDTO;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ComicDetailController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            if (config('comic.mock_api_enable')) {
                return File::json(config_path('mock_json/comic_detail_342.json'));
            }
            $id = $request->id;
            if (!$id) {
                throw new Exception('Missing id param!');
            }

            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $data = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($id) {
                $comic = $this->comicServices->getComicDetail($id);
                return new ComicDTO($comic);
            });

            return $this->commonResponse($data, 'success', 'Success', $inputs);
        } catch (\Exception $e) {
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }
}
