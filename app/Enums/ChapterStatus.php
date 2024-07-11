<?php

namespace App\Enums;

enum ChapterStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'In Active',
        };
    }

    public function value() : string
    {
        return match ($this) {
            self::ACTIVE => 'active',
            self::INACTIVE => 'inactive',
        };
    }
}
