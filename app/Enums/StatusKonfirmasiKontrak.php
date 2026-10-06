<?php

namespace App\Enums;

enum StatusKonfirmasiKontrak: string
{
    case Menunggu = 'menunggu';
    case Diperpanjang = 'diperpanjang';
    case TidakDiperpanjang = 'tidak_diperpanjang';
    case DiangkatTetap = 'diangkat_tetap';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu Konfirmasi',
            self::Diperpanjang => 'Diperpanjang',
            self::TidakDiperpanjang => 'Tidak Diperpanjang',
            self::DiangkatTetap => 'Diangkat Karyawan Tetap',
        };
    }
}
