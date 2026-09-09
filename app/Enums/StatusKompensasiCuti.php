<?php

namespace App\Enums;

enum StatusKompensasiCuti: string
{
    case MenungguDiproses = 'menunggu_diproses';
    case Diproses = 'diproses';

    public function label(): string
    {
        return match ($this) {
            self::MenungguDiproses => 'Menunggu Diproses',
            self::Diproses => 'Diproses',
        };
    }
}
