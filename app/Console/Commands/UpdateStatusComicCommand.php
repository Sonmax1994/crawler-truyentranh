<?php

namespace App\Console\Commands;

use App\Enums\ComicStatus;
use App\Models\Comic;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateStatusComicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:status-comic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'upsert detail comic command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::channel('crawl_log_info')->info('=== Start Crawl Detail Comic Command: ' . now() . ' ===');

            collect(ComicStatus::cases())->map(function ($comicStt) {
                if ($comicStt == ComicStatus::ONGOING) {
                    return true;
                }
                $status = $comicStt->slugStatus();
                $endPoint = 'danh-sach/' . $status;
                $urlCrawl = config('comic.url_crawl_comic') . $endPoint;
                Log::channel('crawl_log_info')->info('=== Crawl truyện status : ' . $status . ' - url : ' . $urlCrawl . ' ===');

                $resp = Http::withoutVerifying()
                    ->connectTimeout(30)
                    ->withOptions(["verify"=>false])
                    ->get($urlCrawl)->json();

                $dataResp = data_get($resp, 'data');
                $items = data_get($dataResp, 'items');

                if (empty($items)) {
                    Log::channel('crawl_log_info')->info('=== Không có data truyện : ' . $status . ' - url : ' . $urlCrawl . ' ===');
                    return true;
                }
                $pagination = data_get($dataResp, 'params.pagination');
                $totalItems = $pagination['totalItems'];
                $totalItemsPerPage = $pagination['totalItemsPerPage'];
                $totalPage = ceil($totalItems / $totalItemsPerPage);
                $this->processCrawlComic($urlCrawl, $totalPage, $comicStt);
            });
            Log::channel('crawl_log_info')->info('=== End Crawl Detail Comic Command ' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('crawl_log_error')->error($e->getMessage());
        }
    }

    private function processCrawlComic(string $urlCrawl, int $totalPage, ComicStatus $statusComic)
    {
        $page = 1;
        $pages = range($totalPage, $page);

        foreach ($pages as $page) {
            $url   = $urlCrawl . '?page=' . $page;
            Log::channel('crawl_log_info')->info('== Call Api : ' . $url . ' ==');
            $resp  = Http::get($url)->json();
            $datas = data_get($resp, 'data');
            $items = $datas['items'];

            if (empty($items)) {
                continue;
            } else {
                // Create or update comic
                $process = $this->updateStatusComic($items, $statusComic);
                if (!$process) {
                    continue;
                }
            }
        }

        return;
    }

    private function updateStatusComic(array $listComics = [], ComicStatus $statusComic = ComicStatus::ONGOING)
    {
        $arrComics = [];

        if (!empty($listComics) && count($listComics)) {
            foreach ($listComics as $key => $comic) {
                array_push($arrComics, $comic['_id']);
            }
        }
        if (!empty($arrComics)) {
            $listComics = Comic::whereIn('api_id', $arrComics)
                ->update([
                    'status' => $statusComic
                ]);
        }

        return;
    }

}
