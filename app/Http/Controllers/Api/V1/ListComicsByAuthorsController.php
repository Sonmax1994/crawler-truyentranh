<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\AuthorDTO;
use App\DTO\ComicDTO;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ListComicsByAuthorsController extends Controller
{
    public function __invoke(Request $request)
    {
        $inputs = $request->all();
        try {
            if (config('comic.mock_api_enable')) {
                return File::json(config_path('mock_json/list_comics_by_author.json'));
            }
            $authorId = $request->author_id ?? '';

            if (empty($authorId)) {
                throw new \Exception("author_id is not null.");
            }

            $cacheKey = $this->generateCacheKey($request->url(), $request->query());
            $dataResponse = Cache::remember($cacheKey, $this->cacheSeconds, function () use ($authorId, $inputs) {
                $author = $this->comicServices->getAuthorDetail($authorId);
                $authorDTO = new AuthorDTO($author);
                $comics = $this->comicServices->getListComicsByAuthors($authorId, $inputs);

                $comicDTOs = $comics->getCollection()->map(function ($comic) {
                    return new ComicDTO($comic);
                });

                return $this->customPaginateResponse($comics, $comicDTOs, ['author' => $authorDTO]);
            });

            return $this->commonResponse($dataResponse, 'success', 'Success', $inputs);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->commonResponse([], 'error', $e->getMessage(), $inputs);
        }
    }

}
