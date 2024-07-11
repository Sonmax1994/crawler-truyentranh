<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use App\Jobs\Statistical\StatisticalRankComicJob;
use App\Enums\RankComicType;
use App\Models\Comic;

class StatisticalRankComicCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistical:rank-comic';

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

            collect(RankComicType::cases())->map(function ($rankComicType) {
                // run job statistical view comic by id comic
                StatisticalRankComicJob::dispatch($rankComicType);
            });

            Log::channel('update_table_log_info')->info('=== End Command Statistical View Comic by : ' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('update_table_log_error')->error($e->getMessage());
        }
    }
}
