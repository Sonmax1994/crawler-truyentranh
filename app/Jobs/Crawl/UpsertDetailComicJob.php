<?php

namespace App\Jobs\Crawl;

use App\Enums\ChapterStatus;
use App\Enums\ComicStatus;
use App\Models\Author;
use App\Models\Chapter;
use App\Models\Comic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpsertDetailComicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $comicId
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $jobName = __CLASS__;
            $comic = Comic::findOrFail($this->comicId);
            $urlDetail = config('comic.url_crawl_comic') . 'truyen-tranh/' . $comic->slug;

            Log::channel('crawl_log_info')->info($jobName . ': Start Crawl ComicID: ' . $comic->id . ' -- URL: ' . $urlDetail);

            $resp = Http::withoutVerifying()
                        ->connectTimeout(30)
                        ->withOptions(["verify"=>false])
                        ->get($urlDetail)->json();

            if (!isset($resp['data'])) {
                Log::channel('crawl_log_error')->error($jobName . ': === Error Crawl Detail URL: ' . $urlDetail . ' ===');
                return;
            }
            $data = $resp['data']['item'];
            $chapters = data_get($data, 'chapters.{first}.server_data');

            $updatedAt = $data['updatedAt'];
            $comic->api_updated_at = date('Y-m-d H:i:s', strtotime($updatedAt));
            $comic->total_chapters = count($chapters);
            if (!$comic->content) {
                $comic->content = $this->prepareContent($data['content']);
            }
            $comic->save();

            //Author
            $authors = $data['author'];
            $authorIds = $this->processAuthors($authors, $comic);

            //Chapters
            if (!empty($chapters)) {
                $dataChapterDB = Chapter::where('comic_id', $comic->id)->first();
                $maxChapterDB = $this->getMaxChapterInDb($dataChapterDB);
                $chapterInsert = $this->getChaptersNotExists($chapters, $maxChapterDB);
                Log::channel('crawl_log_info')->info('ComicID: ' . $comic->id . ' NumberChapterDB: ' . $maxChapterDB . ' - Có ' . count($chapterInsert) . ' chapters mới');
                $this->saveChapters($comic->id, $chapterInsert, $dataChapterDB);
            }
            Log::channel('crawl_log_info')->info($jobName . ': === End Crawl Detail Comic' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('crawl_log_error')->error($jobName . ': ' . $e->getMessage());
        }
    }

    private function getMaxChapterInDb($dataChapterDB)
    {
        // get chapter in db max
        $maxChapterDB = 0;

        if (!empty($dataChapterDB)) {
            $chapterDb = data_get($dataChapterDB->list_infor, '*.name');
            $sorted = Arr::sortDesc($chapterDb);
            $maxChapterDB = Arr::first($sorted);
        }

        return $maxChapterDB;
    }

    /**
     * @param $chapters
     * @param $maxChapterDB
     */
    private function getChaptersNotExists($chapters, $maxChapterDB)
    {
        $chaptersInsert = [];
        foreach ($chapters as $chapter) {
            if ($maxChapterDB >= $chapter['chapter_name']) {
                continue;
            }
            $chaptersInsert[] = $chapter;
        }
        return $chaptersInsert;
    }

    /**
     * @param $content
     */
    private function prepareContent($content)
    {
        $regexUrl = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@";

        $content = preg_replace($regexUrl, "{{DOMAIN_LINK}}", $content);

        $matches = preg_match('/.com/', $content);
        if ($matches) {
            $content = Str::replace('.com', '', $content);
        }
        $arrRemove = [
            'NetTruyen',
            'TruyenQQ',
            'Truyentranh8',
            'Hikki Team'
        ];

        $content = str_replace($arrRemove, '{{BRAND_NAME}}', $content);

        return $content;
    }

    /**
     * @param $authors
     * @return mixed
     */
    private function processAuthors($authors, Comic $comic)
    {
        $dataAuthors = [];
        foreach ($authors as $item) {
            if (!$item) {
                continue;
            }
            $dataAuthors[$item] = [
                'name' => $item,
                'slug' => getSlug($item),
            ];
        }

        if (empty($dataAuthors)) {
            return;
        }

        $authorNames = array_keys($dataAuthors);
        $arrAuthorExists = Author::whereIn('name', $authorNames)->pluck('name')->toArray();
        foreach ($arrAuthorExists as $authorId) {
            unset($dataAuthors[$authorId]);
        }

        Author::insert(array_values($dataAuthors));
        // insert pivot ComicAuthor
        $listAuthors = Author::whereIn('name', $authorNames)->pluck('id')->toArray();
        $comic->authors()->sync($listAuthors);

        return;
    }

    private function saveChapters($comicId, $chapterInsert, $dataChapterDb)
    {
        $chaptersApiData = data_get($chapterInsert, '*.chapter_api_data');
        $arrId = [];
        foreach ($chaptersApiData as $chapter) {
            $arrId[] = str_replace(config('comic.url_chapter_api'), '', $chapter);
        }
        //Check exists chapters
        $arrChapterExists = [];
        if (!empty($dataChapterDb)) {
            $arrChapterExists = data_get($dataChapterDb->list_infor, '*.chapter_api');
        }

        $chapterUrls = array_diff($arrId, $arrChapterExists);
        if (!empty($chapterUrls)) {
            //Get detail chapters
            foreach ($chapterUrls as $url) {
                UpSertChapterJob::dispatch($url, $comicId);
            }
        }

        return;
    }

}
