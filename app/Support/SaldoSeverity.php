<?php

namespace App\Support;

/**
 * Klasifikasi urgensi satu baris saldo cuti (aman/menipis/minus/tanpa
 * batas) beserta warnanya untuk dipakai di luar frontend (Excel, PDF).
 *
 * Ambang batas & warna di sini HARUS selalu sama dengan
 * resources/js/lib/saldo-severity.ts (severity) dan
 * resources/js/components/status-badge.tsx-style badge (warnaBadge) —
 * kalau salah satu berubah, sesuaikan juga yang lain.
 */
class SaldoSeverity
{
    public const UNLIMITED = 'unlimited';

    public const SAFE = 'safe';

    public const WARNING = 'warning';

    public const CRITICAL = 'critical';

    /**
     * Kuota/sisa null berarti jenis cuti tanpa batas — bukan "belum
     * diisi", jadi tidak boleh dianggap warning/critical.
     */
    public static function hitung(?int $sisa, ?int $kuota): string
    {
        if ($sisa === null || $kuota === null) {
            return self::UNLIMITED;
        }

        if ($sisa < 0) {
            return self::CRITICAL;
        }

        $rasio = $kuota > 0 ? $sisa / $kuota : ($sisa > 0 ? 1 : 0);

        return $rasio <= 0.25 ? self::WARNING : self::SAFE;
    }

    /**
     * Warna teks saja (hex tanpa '#'), setara kelas text-*-700 yang
     * dipakai tabel Laporan Saldo Cuti di frontend — dipakai di PDF
     * supaya tampilannya sama seperti di layar.
     */
    public static function warnaTeks(string $severity): string
    {
        return match ($severity) {
            self::UNLIMITED => '0369A1', // sky-700
            self::SAFE => '1D4ED8', // blue-700
            self::WARNING => 'B45309', // amber-700
            self::CRITICAL => 'B91C1C', // red-700
            default => '000000',
        };
    }

    /**
     * Gaya badge (latar lembut + teks tebal senada), setara badge di
     * saldo-severity.ts — dipakai di ekspor Excel.
     *
     * @return array{fill: string, teks: string}
     */
    public static function warnaBadge(string $severity): array
    {
        return match ($severity) {
            self::UNLIMITED => ['fill' => 'E0F2FE', 'teks' => '075985'], // sky
            self::SAFE => ['fill' => 'DBEAFE', 'teks' => '1E40AF'], // blue
            self::WARNING => ['fill' => 'FEF3C7', 'teks' => '92400E'], // amber
            self::CRITICAL => ['fill' => 'FEE2E2', 'teks' => '991B1B'], // red
            default => ['fill' => 'FFFFFF', 'teks' => '000000'],
        };
    }
}
