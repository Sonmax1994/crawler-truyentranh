<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\ChapterDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ChapterDetailController extends Controller
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
                return File::json(config_path('mock_json/chapter_detail_109.json'));
            }
            $comicId = $request->comic_id;
            $chapterName = $request->chapter_name;

            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $data = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($comicId, $chapterName, $inputs) {
                $chapter = $this->chapterServices->getChapterNewDetail($comicId);
                return new ChapterDTO($chapter, $chapterName);
            });

            if (!$data->name) {
                return $this->commonResponse([], 'error', 'Not found chapter name!', $inputs);
            }

            return $this->commonResponse($data, 'success', 'Success', $inputs);
        } catch (\Exception $e) {
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }
}
