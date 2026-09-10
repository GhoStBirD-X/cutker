<?php

namespace App\Enums;

enum StatusCutiMassal: string
{
    case Aktif = 'aktif';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Dibatalkan => 'Dibatalkan',
        };
    }
}
