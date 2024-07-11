<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\Comic;
use App\Models\ChapterOld;

class UpdateTableChapterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:table-chapter';

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
            Log::channel('update_table_log_info')->info('=== Start Update Table Chapter Command: ' . now() . ' ===');

            $countComicUpdate = 0;
            Comic::chunk(100, function (Collection $comics) use (&$countComicUpdate) {
                foreach ($comics as $comic) {
                    $arrChapterNews = [];
                    $chapters = ChapterOld::where('comic_id', $comic->id)->get();
                    if (count($chapters)) {
                        // update total_chapter for comic
                        Comic::where('id', $comic->id)->update([
                            'total_chapters' => count($chapters)
                        ]);
                        $infor = [];
                        foreach ($chapters as $chapter) {
                            $infor[] = [
                                'name'         => $chapter->name,
                                'chapter_api'  => str_replace(config('comic.url_chapter_api'), '', $chapter->chapter_api),
                                'path'         => $chapter->path,
                                'number_image' => $chapter->number_image,
                                'created_at'   => $chapter->created_at,
                            ];
                        }
                        $arrChapterNews = [
                            'comic_id'   => $comic->id,
                            'list_infor' => $infor,
                            'status'     => ChapterStatus::ACTIVE->value,
                        ];
                        $countComicUpdate++;
                        Chapter::updateOrCreate([
                            'comic_id' => $comic->id
                        ], $arrChapterNews);
                    }
                }
            });
            Log::channel('update_table_log_info')->info('=== Update Number Comic :' . $countComicUpdate . ' ***');
            Log::channel('update_table_log_info')->info('=== End Update Table Chapter Command ' . " -- " . now() . ' ***');
        } catch (\Exception $e) {
            Log::channel('update_table_log_error')->error($e->getMessage());
        }
    }
}
