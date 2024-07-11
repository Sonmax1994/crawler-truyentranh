<?php

namespace App\Console\Commands;

use App\Enums\CategoryStatus;
use App\Enums\ChapterStatus;
use App\Enums\ComicStatus;
use App\Jobs\Crawl\UpsertDetailComicJob;
use App\Models\Category;
use App\Models\Comic;
use App\Models\ComicCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpsertDetailComicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upsert:detail-comic';

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
                // get lasted time update comic
                $timeLastedUpdate = $this->getTimeLastedUpdateComic($comicStt);
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
                $this->processCrawlComic($urlCrawl, $totalPage, $timeLastedUpdate, $comicStt);
            });
            Log::channel('crawl_log_info')->info('=== End Crawl Detail Comic Command ' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('crawl_log_error')->error($e->getMessage());
        }
    }

    private function processCrawlComic(string $urlCrawl, int $totalPage, int $timeLastedUpdate, ComicStatus $statusComic)
    {
        $page = 1;
        $pages = range($page, $totalPage);
        if ($timeLastedUpdate == 0) {
            $pages = range($totalPage, $page);
        }
        foreach ($pages as $page) {
            $url   = $urlCrawl . '?page=' . $page;
            $resp  = Http::connectTimeout(30)->withoutVerifying()->withOptions(["verify"=>false])->get($url)->json();
            $datas = data_get($resp, 'data');
            $items = $datas['items'];
            Log::channel('crawl_log_info')->info('=== Crawl url: ' . $url . ' === have: ' . count($items) . ' comics');

            if (empty($items)) {
                break;
            } else {
                // Create or update comic
                $process = $this->procesInsertOrUpdateComic($items, $timeLastedUpdate, $statusComic);
                if (!$process) {
                    break;
                }
            }
        }

        return;
    }

    private function procesInsertOrUpdateComic(
        array $listComics = [],
        int $timeLastedUpdate = 0,
        ComicStatus $statusComic = ComicStatus::ONGOING
    ) {
        $arrComics = [];
        $isCheckProcess = true;
        if (!empty($listComics) && count($listComics)) {
            foreach ($listComics as $key => $comic) {
                $updatedAt = $comic['updatedAt'];
                $timeUpdateAt = strtotime($updatedAt);
                if ($timeUpdateAt >= $timeLastedUpdate) {
                    $arrComics[] = [
                        'api_id'         => $comic['_id'],
                        'name'           => $comic['name'],
                        'slug'           => $comic['slug'],
                        'status'         => $statusComic,
                        'sub_docquyen'   => $comic['sub_docquyen'],
                        'thumb_url'      => $comic['thumb_url'],
                        'listCategories' => $comic['category'],
                    ];
                } else {
                    $isCheckProcess = false;
                }
            }
        }
        if (count($arrComics)) {
            $arrComicIdApis = Arr::pluck($arrComics, 'api_id');
            $listComics = Comic::whereIn('api_id', $arrComicIdApis)
                ->get();

            $arrApiId = $listComics->pluck('api_id')->toArray();
            foreach ($arrComics as $data) {
                $comicIdApi = $data['api_id'];
                if (!count($arrApiId) || !in_array($comicIdApi, $arrApiId)) {
                    foreach ($data['listCategories'] as $cate) {
                        $arrCateApiIds[] = data_get($cate, 'id');
                    }
                    Arr::forget($data, 'listCategories');
                    $dataCheckCate = [];
                    if (!empty($arrCateApiIds)) {
                        $dataCheckCate = Category::whereIn('api_id', $arrCateApiIds)
                            ->pluck('id')
                            ->toArray();
                    }
                    $comicDetail = collect();
                    $comic = Comic::updateOrCreate([
                        'api_id' => $data['api_id']
                    ], $data);
                    $comicDetail = clone($comic);
                    //Insert table pivot comic_category
                    $comic->categories()->sync($dataCheckCate);
                } else {
                    $comicDetail = $listComics->first(function ($item, $key) use ($comicIdApi) {
                        return $comicIdApi == $item->api_id;
                    });
                }
                // run job crawl comic detail
                UpsertDetailComicJob::dispatch($comicDetail->id);
            }
        }

        return $isCheckProcess;
    }

    /**
     * @return $timestamp
     */
    private function getTimeLastedUpdateComic(ComicStatus $statusComic)
    {
        // get comic update lasted
        $comicLastedUpdate = Comic::select('api_updated_at')
            ->where('status', $statusComic)
            ->orderBy('api_updated_at', 'DESC')
            ->first();
        $timeLastedUpdate = 0;
        if (!empty($comicLastedUpdate)) {
            $timeLastedUpdate = strtotime($comicLastedUpdate->api_updated_at);
        }

        return $timeLastedUpdate;
    }

}
