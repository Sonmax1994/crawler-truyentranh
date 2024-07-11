<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use App\Jobs\Statistical\UpdateViewComicJob;
use App\Enums\ComicStatus;
use App\Models\Comic;

class StatisticalViewComicsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistical:view-comics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::channel('update_table_log_info')->info('=== Start Command Statistical View Comic by : ' . now() . ' ===');

            Comic::chunkById(100, function ($comics) {
                $arrIdComics = $comics->pluck('id')->toArray();
                // run job statistical view comic by id comic
                UpdateViewComicJob::dispatch($arrIdComics);
            });

            Log::channel('update_table_log_info')->info('=== End Command Statistical View Comic by : ' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('update_table_log_error')->error($e->getMessage());
        }
    }
}
