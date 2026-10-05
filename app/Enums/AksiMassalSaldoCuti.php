<?php

namespace App\Enums;

enum AksiMassalSaldoCuti: string
{
    case SetKuota = 'set_kuota';
    case TambahKuota = 'tambah_kuota';
    case KurangiKuota = 'kurangi_kuota';
    case Hapus = 'hapus';

    public function label(): string
    {
        return match ($this) {
            self::SetKuota => 'Set kuota menjadi',
            self::TambahKuota => 'Tambah kuota & sisa',
            self::KurangiKuota => 'Kurangi kuota & sisa',
            self::Hapus => 'Hapus baris saldo',
        };
    }

    public function butuhNilai(): bool
    {
        return $this !== self::Hapus;
    }
}
