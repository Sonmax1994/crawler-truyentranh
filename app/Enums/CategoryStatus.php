<?php

namespace App\Enums;

enum CategoryStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    public function getLabel(): string
    {
        return match ($this) {
            self::INACTIVE => 'In Active',
            self::ACTIVE => 'Active',
        };
    }
}
