<?php

namespace App\Jobs\Statistical;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Enums\RankComicType;
use App\Models\Comic;
use App\Models\RankComic;
use Carbon\Carbon;

class StatisticalRankComicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected RankComicType $rankComicType
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
            Log::channel('update_table_log_info')->info($jobName . ': Start Job Statistical Rank Comic: ' . now());

            switch ($this->rankComicType) {
                case RankComicType::WEEK:
                    $this->processGetRankComicForWeek();
                    break;
                case RankComicType::MONTH:
                    $this->processGetRankComicForMonth();
                    break;
                default:
                    $this->processGetRankComicForDay();
                    break;
            }

            Log::channel('update_table_log_info')->info($jobName . ': === End Job Statistical Rank Comic : ' . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('update_table_log_error')->error($jobName . ': ' . $e->getMessage());
        }
    }

    private function createOrNewRankComic(string $valueRank = '')
    {
        return RankComic::firstOrCreate([
            'value' => $valueRank,
            'type'  => $this->rankComicType
        ]);
    }

    private function orderByComic(string $orderBy = '')
    {
        return Comic::orderByRaw($orderBy)
            ->limit(10)
            ->pluck('id')
            ->toArray();
    }

    private function processGetRankComicForDay()
    {
        $valueRank = date('Ymd');

        $rankComic = $this->createOrNewRankComic($valueRank);

        $comics = $this->orderByComic('CAST(JSON_EXTRACT(info_views, "$.view_today")  AS UNSIGNED) desc');

        if (!empty($comics)) {
            $rankComic->rank_info = $comics;
        }
        $rankComic->save();
        return;
    }

    private function processGetRankComicForWeek()
    {
        // insert rank comic for current week
        $now = Carbon::now();
        $valueRank = $now->weekOfYear . '_' . date('Y');
        
        $rankComic = $this->createOrNewRankComic($valueRank);

        $comics = $this->orderByComic('CAST(JSON_EXTRACT(info_views, "$.view_for_week.current")  AS UNSIGNED) desc');

        if (!empty($comics)) {
            $rankComic->rank_info = $comics;
        }
        $rankComic->save();

        // insert or update rank for comic for before week
        $valueRank = $now->subWeek()->weekOfYear . '_' . date('Y'); 
        
        $rankComic = $this->createOrNewRankComic($valueRank);
        $comics = $this->orderByComic('CAST(JSON_EXTRACT(info_views, "$.view_for_week.current")  AS UNSIGNED) desc');

        if (!empty($comics)) {
            $rankComic->rank_info = $comics;
        }

        $rankComic->save();

        return;
    }

    private function processGetRankComicForMonth()
    {
        // insert rank comic for current month
        $now = Carbon::now();
        $valueRank = $now->month . date('Y');
        
        $rankComic = $this->createOrNewRankComic($valueRank);

        $comics = $this->orderByComic('CAST(JSON_EXTRACT(info_views, "$.view_for_month.current")  AS UNSIGNED) desc');

        if (!empty($comics)) {
            $rankComic->rank_info = $comics;
        }
        $rankComic->save();

        // insert or update rank for comic for before month
        $valueRank = $now->subMonth()->month . date('Y'); 
        
        $rankComic = $this->createOrNewRankComic($valueRank);
        $comics = $this->orderByComic('CAST(JSON_EXTRACT(info_views, "$.view_for_month.current")  AS UNSIGNED) desc');

        if (!empty($comics)) {
            $rankComic->rank_info = $comics;
        }

        $rankComic->save();

        return;
    }
}
