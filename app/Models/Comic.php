<?php

namespace App\Models;

use App\Enums\ComicStatus;
use App\Models\Author;
use App\Models\Category;
use App\Models\ComicAuthor;
use App\Models\ComicCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comic extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'api_id',
        'name',
        'other_name',
        'slug',
        'thumb_url',
        'description',
        'sub_docquyen',
        'view',
        'content',
        'status',
        'api_updated_at',
        'total_chapters',
        'info_views',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'api_id',
        'deleted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'     => ComicStatus::class,
            'info_views' => 'array',
        ];
    }

    /**
     * @return array
     */
    public static function getComicStatus(): array
    {
        return [
            ComicStatus::ONGOING->value => ComicStatus::ONGOING->value(),
            ComicStatus::COMPLETED->value => ComicStatus::COMPLETED->value(),
            ComicStatus::COMING_SOON->value => ComicStatus::COMING_SOON->value(),
        ];
    }

    /**
     * @return int
     */
    public static function getComicStatusInt($status): int
    {
        $arrStatus = array_reverse(Comic::getComicStatus());
        return @$arrStatus[$status] ?? 0;
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'comic_category', 'comic_id', 'category_id');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'comic_author', 'comic_id', 'author_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function chapter()
    {
        return $this->hasOne(Chapter::class);
    }

}
