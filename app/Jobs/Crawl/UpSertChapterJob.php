<?php

namespace App\Jobs\Crawl;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Enums\ChapterStatus;
use App\Models\Chapter;

class UpSertChapterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $urlChap,
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
            Log::channel('crawl_log_info')->info($jobName . ': === Start: ' . now() . ' ===');

            $resp = Http::withoutVerifying()
                        ->connectTimeout(30)
                        ->withOptions(["verify"=>false])
                        ->get(config('comic.url_chapter_api') . $this->urlChap)->json();

            if (!isset($resp['data'])) {
                Log::channel('crawl_log_error')->error($jobName . ': === Error Crawl Detail Chapter URL: ' . $this->urlChap . ' ===');
                return;
            }

            $detailChapter = data_get($resp, 'data.item');

            $dataChapter = Chapter::where('comic_id', $this->comicId)->firstOrNew();
            $newInfor = [
                'name'         => $detailChapter['chapter_name'],
                'chapter_api'  => $detailChapter['_id'],
                'path'         => $detailChapter['chapter_path'],
                'number_image' => count($detailChapter['chapter_image']),
                'created_at'   => date('Y-m-d H:i:s'),
            ];

            $listInfor = $dataChapter->list_infor ?? [];
            if (!empty($listInfor)) {
                array_push($listInfor, $newInfor);
            } else {
                $listInfor = [$newInfor];
            }
            $dataChapter->comic_id = $this->comicId;
            $dataChapter->list_infor = $listInfor;
            $dataChapter->status = ChapterStatus::ACTIVE->value;
            $dataChapter->save();

            Log::channel('crawl_log_info')->info($jobName . ': === End Crawl Detail Chapter' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('crawl_log_error')->error($jobName . ': ' . $e->getMessage());
        }
    }
}
