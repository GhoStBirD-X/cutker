<?php

namespace App\Services;

use App\Enums\SumberHariLibur;
use App\Models\HariLibur;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HariLiburService
{
    protected const SUMBER_URL = 'https://raw.githubusercontent.com/guangrei/APIHariLibur_V2/main/calendar.json';

    /**
     * Sinkronkan hari libur nasional untuk satu tahun dari kalender
     * Indonesia (sumber eksternal statis di GitHub, tanpa API key).
     * Kegagalan jaringan tidak melempar exception — CRUD manual di Master
     * Data jadi fallback saat sinkronisasi tidak tersedia.
     */
    public function sync(int $tahun): int
    {
        try {
            $response = Http::get(self::SUMBER_URL);
        } catch (Throwable $e) {
            Log::warning("Gagal sinkronisasi hari libur nasional tahun {$tahun}: {$e->getMessage()}");

            return 0;
        }

        if (! $response->successful()) {
            Log::warning("Gagal sinkronisasi hari libur nasional tahun {$tahun}: HTTP {$response->status()}");

            return 0;
        }

        $disinkron = 0;

        foreach ($response->json() ?? [] as $tanggal => $info) {
            if (! is_array($info) || ($info['holiday'] ?? false) !== true) {
                continue;
            }

            if (! str_starts_with((string) $tanggal, (string) $tahun)) {
                continue;
            }

            HariLibur::query()->updateOrCreate(
                ['tanggal' => $tanggal],
                [
                    'keterangan' => implode(', ', (array) ($info['summary'] ?? ['Hari libur nasional'])),
                    'sumber' => SumberHariLibur::Nasional,
                ],
            );

            $disinkron++;
        }

        return $disinkron;
    }

    /**
     * Hitung jumlah hari libur (dari tabel lokal) yang bertabrakan dengan
     * rentang tanggal cuti, dipakai untuk memotong jumlah_hari pengajuan.
     */
    public function countBetween(Carbon $mulai, Carbon $selesai): int
    {
        return HariLibur::query()
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])
            ->count();
    }
}
