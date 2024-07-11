<?php

namespace App\Services;

use App\Enums\ComicStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\Comic;
use Illuminate\Support\Facades\DB;
use App\Enums\RankComicType;
use App\Models\RankComic;
use Carbon\Carbon;

class ComicServices
{
    protected $domainThumbApi;
    protected $thumbApiUrl;
    protected $domainChapterAPI;

    public function __construct()
    {
        $this->domainThumbApi = config('comic.thumb_domain_api');
        $this->thumbApiUrl = $this->domainThumbApi . 'uploads/comics/';
        $this->domainChapterAPI = config('comic.chapter_domain_api');
    }

    /**
     * Function get list category
     *
     * @return array list Category
     */
    public function getListCategory()
    {
        return Category::all();
    }

    /**
     * @param $id
     * @return Author[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getAuthorDetail($id)
    {
        return Author::findOrFail($id);
    }

    /**
     * @param $id
     * @return Comic[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getComicDetail($id)
    {
        return Comic::findOrFail($id);
    }

    /**
     * Function get list comic by category
     *
     * @params array $idsCategory
     * @params array $comicParams
     * @return array list Category
     */
    public function getListComicsByCate(array $idsCategory = [], array $comicParams = [])
    {
        $comicIds = Category::whereIn('id', $idsCategory)
            ->join('comic_category AS cc', 'cc.category_id', '=', 'categories.id')
            ->pluck('comic_id')
            ->toArray();
        if (empty($comicIds)) {
            return [];
        }

        return $this->conditionQuery($comicIds, $comicParams, ['authors', 'categories']);
    }

    /**
     * Function get list comic by category
     *
     * @params integer $authorId
     * @params array $comicParams
     * @return array list Category
     */
    public function getListComicsByAuthors(int $authorId = 0, array $comicParams = [])
    {
        $comicIds = Author::where('id', $authorId)
            ->join('comic_author AS ca', 'ca.author_id', '=', 'authors.id')
            ->pluck('comic_id')
            ->toArray();
        if (empty($comicIds)) {
            return [];
        }

        return $this->conditionQuery($comicIds, $comicParams, ['categories']);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getLatestComics($params)
    {
        $params['per_page'] = $params['per_page'] ?? 18;
        $params['sort'] = 'api_updated_at';
        $params['order'] = 'desc';
        return $this->conditionQuery([], $params, ['categories', 'authors']);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getComicsCompleted($params)
    {
        $params['status'] = ComicStatus::COMPLETED->value;
        $params['per_page'] = $params['per_page'] ?? 18;

        return $this->conditionQuery([], $params, ['categories', 'authors']);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getComicsPopular($params)
    {
        $params['per_page'] = data_get($params, 'per_page', 18);
        $params['sort'] = 'view';
        $params['order'] = 'desc';

        return $this->conditionQuery([], $params, ['categories', 'authors']);
    }

    /**
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchComic(array $params = [])
    {
        return $this->conditionQuery([], $params, ['authors']);
    }

    /**
     * @param $comicId
     * @return mixed
     */
    public function updateViewComic($comicId)
    {
        Comic::updateOrCreate(
            ['id' => $comicId],
            ['view' => DB::raw('view + 1')]
        );
        return $this->getComicDetail($comicId);
    }

    /**
     * @param array $comicIds
     * @param array $params
     * @param array $with
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function conditionQuery($comicIds = [], $params = [], $with = [])
    {
        $query = (new Comic)->newQuery();

        if (!empty($with)) {
            $query->with($with);
        }

        if (count($comicIds)) {
            $query->whereIn('id', $comicIds);
        }

        if (isset($params['status'])) {
            $query->where('status', (int)$params['status']);
        }

        if (isset($params['keyword'])) {
            $query->where(function ($subQuery) use ($params) {
                $subQuery->where('name', 'like', "%" . $params['keyword'] . "%")
                    ->orWhereHas('authors', function ($q) use ($params) {
                        $keyword = getSlug($params['keyword']);
                        return $q->where('slug', 'like', "%" . $keyword . "%");
                    });
            });
        }

        if (isset($params['api_id'])) {
            $query->where('api_id', $params['api_id']);
        }

        // sort list comic
        if (isset($params['sort'])) {
            $order = $params['order'] ?? 'asc';
            switch ($params['sort']) {
                case 'api_updated_at':
                    $query->orderBy('api_updated_at', $order);
                    break;
                case 'view':
                    $query->orderBy('view', $order);
                    break;
                case 'top_view':
                    $stringRawOrderBy = $params['raw_order_by'];
                    $query->orderByRaw($stringRawOrderBy);
                    break;
                default:
                    $query->orderBy('updated_at', 'desc');
                    break;
            }
        }

        // pagination
        $perPage = $params['per_page'] ?? 20;
        return $query->paginate($perPage)->appends($params);
    }

    

    /**
     * @param RankComicType $rankComicType
     * @return mixed
     */
    public function listComicTopView(array $inputs = [])
    {
        $typeRank  = $inputs['type'] ?? RankComicType::DAY->value;
        $time = $inputs['time'] ?? 'current';

        $idComics = $this->getListIdComicTopView($typeRank, $time);
        $params = [
            'sort'         => 'top_view',
            'raw_order_by' => $this->stringSortComicTopView($typeRank, $time),
        ];
        return $this->conditionQuery($idComics, $params);
    }

    private function getListIdComicTopView(int $rankComicType, string $time)
    {
        $valueRank = '';
        $now = Carbon::now();
        switch ($rankComicType) {
            case RankComicType::WEEK->value:
                $valueRank = $now->weekOfYear . '_' . date('Y');
                if (!empty($time) && $time == 'before') {
                    $valueRank = $now->subWeek()->weekOfYear . '_' . date('Y'); 
                }
                break;
            case RankComicType::MONTH->value:
                $valueRank = $now->month . date('Y');
                if (!empty($time) && $time == 'before') {
                    $valueRank = $now->subMonth()->month . date('Y'); 
                }
                break;
            default:
                $valueRank = date('Ymd');
                break;
        }
        return $this->detailRankComic($rankComicType, $valueRank);
    }

    private function detailRankComic(int $rankComicType = 1, string $valueRank = '')
    {
        return RankComic::where('value', $valueRank)
            ->where('type', $rankComicType)
            ->pluck('rank_info')
            ->first();
    }

    private function stringSortComicTopView($typeRank, $time)
    {
        switch ($typeRank) {
            case RankComicType::WEEK->value:
                $rawOrderBy = 'CAST(JSON_EXTRACT(info_views, "$.view_for_week.' . $time .'")  AS UNSIGNED) desc';
                break;
            case RankComicType::MONTH->value:
                $rawOrderBy = 'CAST(JSON_EXTRACT(info_views, "$.view_for_month.' . $time . '")  AS UNSIGNED) desc';
                break;
            default:
                $rawOrderBy = 'CAST(JSON_EXTRACT(info_views, "$.view_today")  AS UNSIGNED) desc';
                break;
        }

        return $rawOrderBy;
    }
}
