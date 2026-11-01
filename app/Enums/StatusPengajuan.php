<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Pending = 'pending';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dibatalkan = 'dibatalkan';

    /**
     * Tanggal cuti melewati akhir periode saldo aktif — belum masuk alur
     * approval & belum memotong saldo. Karyawan mengajukan ulang sendiri
     * setelah periode saldo diperbarui.
     */
    case Ditampung = 'ditampung';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
            self::Ditampung => 'Ditampung',
        };
    }
}
