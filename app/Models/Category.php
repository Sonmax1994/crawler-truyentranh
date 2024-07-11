<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\CategoryStatus;
use App\Models\Comic;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'api_id',
        'name',
        'slug',
        'description',
        'content',
        'status',
        'created_at',
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
            'status' => CategoryStatus::class,
            'created_at' => 'datetime'
        ];
    }

    public function comics(): BelongsToMany
    {
        return $this->belongsToMany(Comic::class, 'comic_category', 'category_id', 'comic_id');
    }
}
