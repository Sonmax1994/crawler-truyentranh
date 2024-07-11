<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\CategoryDTO;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __invoke()
    {
        try {
            if (config('comic.mock_api_enable')) {
                return File::json(config_path('mock_json/categories.json'));
            }
            $cacheName = 'Categories';
            $listCate = Cache::remember($cacheName, $this->cacheSeconds, function () {
                $categories = $this->comicServices->getListCategory();

                return $categories->map(function ($category) {
                    return new CategoryDTO($category);
                });
            });

            return $this->commonResponse($listCate, 'success', 'Success');
        } catch (\Exception $e) {
            return $this->commonResponse([], 'error', 'Error');
        }
    }

}
