<?php

use App\Http\Controllers\Api\V1\CategoriesController;
use App\Http\Controllers\Api\V1\ChapterDetailController;
use App\Http\Controllers\Api\V1\ComicDetailController;
use App\Http\Controllers\Api\V1\ComicsUpdatedController;
use App\Http\Controllers\Api\V1\ListComicsByCategoryController;
use App\Http\Controllers\Api\V1\ListComicsByAuthorsController;
use App\Http\Controllers\Api\V1\SearchComicsController;
use App\Http\Controllers\Api\V1\ComicsCompletedController;
use App\Http\Controllers\Api\V1\ComicsPopularController;
use App\Http\Controllers\Api\V1\UpdateViewComicController;
use App\Http\Controllers\Api\V1\RankComicController;
use Illuminate\Support\Facades\Route;

Route::get('categories', [CategoriesController::class, '__invoke']);
Route::get('categories/list-comic', [ListComicsByCategoryController::class, '__invoke']);
Route::get('authors/list-comic/{author_id}', [ListComicsByAuthorsController::class, '__invoke']);
Route::get('comic/{id}', [ComicDetailController::class, '__invoke']);
Route::get('chapter/{comic_id}/{chapter_name}', [ChapterDetailController::class, '__invoke'])->name('detail.chapter');
Route::get('search-comic', [SearchComicsController::class, '__invoke'])->name('search_comic');
Route::get('comics-updated', [ComicsUpdatedController::class, '__invoke'])->name('comic_updated');
Route::get('comics-completed', [ComicsCompletedController::class, '__invoke'])->name('comic_completed');
Route::get('comics-popular', [ComicsPopularController::class, '__invoke'])->name('comic_popular');
Route::put('update-view-comic/{comic_id}', [UpdateViewComicController::class, '__invoke'])->name('update_view_comic');
Route::get('rank-comic', [RankComicController::class, '__invoke'])->name('rank_comic');
