<?php

namespace App\Services;

use App\Models\Chapter;

class ChapterServices
{

    /**
     * @param int $comicId
     * @return mixed
     */
    public function getChapterNewDetail(int $comicId)
    {
        return Chapter::where('comic_id', $comicId)->first();
    }

    /**
     * @param array $listChapters
     * @param string|string $sortBy
     * @return array
     */
    public function sortChapters(array $listChapters, string $sortBy = 'asc'): array
    {
        return collect($listChapters)
            ->sortBy('name', SORT_REGULAR, $sortBy !== 'asc')
            ->values()
            ->toArray();
    }

    /**
     * @param $comic
     * @return mixed
     */
    public function getLatestChapter(object $comic)
    {
        if (!isset($comic->chapter) || !isset($comic->chapter->list_infor)) {
            return [];
        }
        $sortedChapters = $this->sortChapters($comic->chapter->list_infor, 'desc');
        return $sortedChapters[0] ?? [];
    }


    /**
     * @param $comic
     * @param $sortBy
     * @return array
     */
    public function getListChapterSorted(object $comic, string $sortBy = 'desc'): array
    {
        if (!isset($comic->chapter) || !isset($comic->chapter->list_infor)) {
            return [];
        }

        return $this->sortChapters($comic->chapter->list_infor, $sortBy);
    }

}
