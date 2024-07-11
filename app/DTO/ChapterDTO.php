<?php

namespace App\DTO;

use Carbon\Carbon;

class ChapterDTO
{
    public $comic_id;
    public $comic_name;
    public $name;
    public $chapter_image;
    public $created_at;

    public $prev;
    public $next;

    public function __construct($chapter, $chapterName)
    {
        $collectionList = collect($chapter->list_infor)->sortBy('name')->values();
        $chapterInfo = $collectionList->first(function ($item) use ($chapterName) {
            return $item['name'] == $chapterName;
        });
        if (!$chapterInfo) {
            return [];
        }

        $this->comic_id = $chapter->comic_id;
        $this->comic_name = $chapter->comic->name;
        $this->name = $chapterInfo['name'];
        $this->created_at = Carbon::parse($chapterInfo['created_at'])->format('Y-m-d H:i:s');

        //Chapter Images
        $chapterDomainApi = config('comic.chapter_domain_api');
        $dataImages = array_map(function ($i) use ($chapterDomainApi, $chapterInfo) {
            return [
                "image_page" => $i,
                "image_file" => $chapterDomainApi . $chapterInfo['path'] . "/page_$i.jpg"
            ];
        }, range(1, $chapterInfo['number_image'] - 1));
        $this->chapter_image = $dataImages;

        //Get prev and next
        $currentIndex = $collectionList->search(function ($item) use ($chapterName) {
            return $item['name'] == $chapterName;
        });
        $this->prev = $collectionList->get($currentIndex - 1);
        $this->next = $collectionList->get($currentIndex + 1);
    }
}
