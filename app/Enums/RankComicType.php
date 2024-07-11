<?php

namespace App\Enums;

enum RankComicType: int
{
    case DAY   = 1;
    case WEEK  = 2;
    case MONTH = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::DAY   => 'This Day',
            self::WEEK  => 'This Week',
            self::MONTH => 'This Month',
        };
    }

}
