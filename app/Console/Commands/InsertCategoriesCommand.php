<?php

namespace App\Console\Commands;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InsertCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:list-category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'insert or update list category';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $endPoint = 'the-loai';
            $urlCrawl = config('comic.url_crawl_comic') . $endPoint;
            Log::channel('crawl_log_info')->info('=== Start Crawl List Category Command : ' . now() . ' ===');
            Log::channel('crawl_log_info')->info('Url Crawl : ' . $urlCrawl);

            $resp = Http::withoutVerifying()
                ->connectTimeout(30)
                ->withOptions(["verify"=>false])
                ->get($urlCrawl)->json();

            $datas = data_get($resp, 'data.items');
            if (!empty($datas) && count($datas)) {
                $dataInsert = [];
                $dataCates = Category::pluck('api_id')->toArray();
                foreach ($datas as $key => $data) {
                    $cateIdApi = data_get($data, '_id');
                    if (!count($dataCates) || !in_array($cateIdApi, $dataCates)) {
                        $dataInsert[] = [
                            'api_id' => $cateIdApi,
                            'name'   => data_get($data, 'name'),
                            'slug'   => data_get($data, 'slug'),
                            'status' => CategoryStatus::ACTIVE->value,
                        ];
                    }
                }
                // insert new category
                if (count($dataInsert)) {
                    Category::insert($dataInsert);
                }
            }
            Log::channel('crawl_log_info')->info('=== End Crawl List Category Command ' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('crawl_log_error')->error($e->getMessage());
        }
    }
}
