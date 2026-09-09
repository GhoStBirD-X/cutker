<?php

namespace App\Enums;

enum JenisKelamin: string
{
    case LakiLaki = 'laki_laki';
    case Perempuan = 'perempuan';

    public function label(): string
    {
        return match ($this) {
            self::LakiLaki => 'Laki-laki',
            self::Perempuan => 'Perempuan',
        };
    }
}
