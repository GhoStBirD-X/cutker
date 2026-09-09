<?php

namespace App\Enums;

enum SumberHariLibur: string
{
    case Nasional = 'nasional';
    case Perusahaan = 'perusahaan';

    public function label(): string
    {
        return match ($this) {
            self::Nasional => 'Libur Nasional',
            self::Perusahaan => 'Libur Perusahaan',
        };
    }
}
