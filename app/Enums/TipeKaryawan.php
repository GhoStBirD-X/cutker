<?php

namespace App\Enums;

enum TipeKaryawan: string
{
    case Tetap = 'tetap';
    case Kontrak = 'kontrak';

    public function label(): string
    {
        return match ($this) {
            self::Tetap => 'Karyawan Tetap',
            self::Kontrak => 'Karyawan Kontrak',
        };
    }
}
