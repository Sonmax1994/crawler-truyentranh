<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\RankComicType;

class RankComic extends Model
{
    use HasFactory;
	use SoftDeletes;
    
    protected $fillable = [
        'id',
        'type',
        'value',
        'rank_info',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
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
			'type'      => RankComicType::class,
			'rank_info' => 'array',
        ];
    }
}
