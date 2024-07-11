<?php

namespace App\Jobs\Statistical;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Comic;
use Carbon\Carbon;

class UpdateViewComicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $arrIdComics
    )
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $jobName = __CLASS__;
            if (empty($this->arrIdComics)) {
                return;
            }
            $comics = Comic::whereIn('id', $this->arrIdComics)->get();
            if (!count($comics)) {
                return;
            }
            Log::channel('update_table_log_info')->info($jobName . ': Start Job Statistical View Comic: ' . now());

            foreach ($comics as $comic) {
                $inforViews         = $comic->infor_views ?? [];
                $viewToday          = $comic->view ?? 0;
                $infoViewsNew      = $this->processGetInforView($viewToday, $inforViews);
                $comic->view        = 0;
                $comic->info_views = $infoViewsNew;
                $comic->save();
            }

            Log::channel('update_table_log_info')->info($jobName . ': === End Job Statistical View Comic : Number Update '
                . count($comics) . " Comics -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('update_table_log_error')->error($jobName . ': ' . $e->getMessage());
        }
    }

    private function processGetInforView(int $viewToday = 0, array $inforViews = [])
    {
        $newInfors = [];

        // lưu thông tin view của 60 ngày gần nhất
        $inforInDate = [];
        for ($i = 62; $i > 0; $i--) { 
            $key = date('Ymd', strtotime('-' . $i . ' days'));
            $inforInDate[$key] = $inforViews['view_for_day'][$key] ?? 0;
        }
        $viewNow = $inforViews['view_for_day'][date('Ymd')] ?? 0;
        $viewDay = $viewNow + $viewToday;
        $inforInDate[date('Ymd')] = $viewDay;
        $inforTheWeek = [];
        $dayOfWeek = Carbon::now()->dayOfWeek;
        // lưu thông tin view của các ngày trong tuần hiện tại
        $viewCurrentWeek = 0;
        for ($i = $dayOfWeek; $i >= 0; $i--) {
            $key = date('Ymd', strtotime('-' . $i . ' days'));
            $viewCurrentWeek += $inforInDate[$key];
        }
        $inforTheWeek['current'] = $viewCurrentWeek;
        // lưu thông tin view của các ngày trong tuần trước
        $viewWeekBefore = 0;
        for ($i = 6; $i >= 0; $i--) {
            $key = date('Ymd', strtotime('-' . $i + $dayOfWeek . ' days'));
            $viewWeekBefore += $inforInDate[$key];
        }
        $inforTheWeek['before'] = $viewWeekBefore;

        $inforTheMonth = [];
        // lưu thông tin view các ngày của tháng hiện tại
        $viewCurrentMonth = 0;
        for ($i = date('d'); $i >= 1; $i--) {
            $key = date('Ymd', strtotime('-' . $i . 'days'));
            $viewCurrentMonth += $inforInDate[$key] ?? 0;
        }
        $inforTheMonth['current'] = $viewCurrentMonth;
        // lưu thông tin view các ngày của tháng trước
        $viewMonthBefore = 0;
        $dateTime = date('Y-m-d H:i:s', strtotime('-1 days -1 months'));
        $beforeMonth = Carbon::createFromFormat('Y-m-d H:i:s', $dateTime);
        $daysInBeforeMonth = $beforeMonth->daysInMonth;
        for ($i = $daysInBeforeMonth; $i >= 0; $i--) {
            $date = $i + date('d');
            $key = date('Ymd', strtotime('-' . $date . ' days'));
            $viewMonthBefore += $inforInDate[$key] ?? 0;
        }
        $inforTheMonth['before'] = $viewMonthBefore;

        return [
            'view_for_day'   => $inforInDate,
            'view_for_week'  => $inforTheWeek,
            'view_for_month' => $inforTheMonth,
            'view_today'     => $viewDay,
        ];
    }

}
