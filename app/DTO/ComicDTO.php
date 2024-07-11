<?php

namespace App\DTO;

use App\Services\ChapterServices;
use Carbon\Carbon;

class ComicDTO
{
    public $id;
    public $name;
    public $other_name;
    public $slug;
    public $thumb_url;
    public $description;
    public $sub_docquyen;
    public $view;
    public $content;
    public $status;
    public $api_updated_at;
    public $status_label;
    public $total_chapters;
    public $created_at;

    public $latest_chapter;
    public $chapters;

    public $categories;
    public $authors;
    public $info_views;

    public function __construct($comic)
    {
        $this->id = $comic->id;
        $this->name = $comic->name;
        $this->other_name = $comic->other_name;
        $this->slug = $comic->slug;

        $thumbApiUrl = config('comic.thumb_domain_api') . 'uploads/comics/';
        $this->thumb_url = $thumbApiUrl . $comic->thumb_url;

        $this->description = $comic->description;
        $this->sub_docquyen = $comic->sub_docquyen;
        $this->view = $comic->view;
        $this->content = $comic->content;
        $this->status = $comic->status;
        $this->api_updated_at = $comic->api_updated_at;
        $this->status_label = $comic->status->getLabel();
        $this->total_chapters = $comic->total_chapters;
        $this->created_at = Carbon::parse($comic->created_at)->format('Y-m-d H:i:s');

        //Chapter info
        $chapterService = new ChapterServices();
        $this->latest_chapter = $chapterService->getLatestChapter($comic);
        $this->chapters = $chapterService->getListChapterSorted($comic, 'desc');

        //Categories
        $categoryDTOs = $comic->categories->map(function ($category) {
            return new CategoryDTO($category);
        });
        $this->categories = $categoryDTOs;

        //Authors
        $authorDTOs = $comic->authors->map(function ($author) {
            return new AuthorDTO($author);
        });
        $this->authors = $authorDTOs;

        $this->info_views = $comic->info_views;
    }
}
