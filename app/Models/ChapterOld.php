<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ChapterStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChapterOld extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'chapter_api',
        'name',
        'comic_id',
        'path',
        'status',
        'view',
        'filename',
        'number_image',
    ];

    protected $hidden = [
        'chapter_api',
        'deleted_at',
        'pivot'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];


	/**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ChapterStatus::class,
        ];
    }

    public function comic(): BelongsTo
    {
        return $this->belongsTo(Comic::class);
    }

}
