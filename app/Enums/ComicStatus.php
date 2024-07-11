<?php

namespace App\Enums;

enum ComicStatus: int
{
    case ONGOING = 0;
    case COMPLETED = 1;
    case COMING_SOON = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::ONGOING => 'Đang cập nhật',
            self::COMPLETED => 'Hoàn thành',
            self::COMING_SOON => 'Sắp ra mắt',
        };
    }

    public function value() : string
    {
        return match ($this) {
            self::ONGOING => 'ongoing',
            self::COMPLETED => 'completed',
            self::COMING_SOON => 'coming_soon',
        };
    }

    public function slugStatus() : string
    {
        return match ($this) {
            self::ONGOING => 'truyen-moi',
            self::COMING_SOON => 'sap-ra-mat',
            self::COMPLETED => 'hoan-thanh'
        };
    }

    public function getKeys(): array
    {
        return [
        ];
    }
}
