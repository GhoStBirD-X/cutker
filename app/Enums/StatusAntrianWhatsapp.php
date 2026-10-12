<?php

namespace App\Enums;

enum StatusAntrianWhatsapp: string
{
    case Pending = 'pending';
    case Terkirim = 'terkirim';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Dikirim',
            self::Terkirim => 'Terkirim',
            self::Gagal => 'Gagal',
        };
    }
}
