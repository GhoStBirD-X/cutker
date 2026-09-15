---
paths:
  - app/Services/HariLiburService.php
  - app/Services/CutiMassalService.php
---

# Services

## Hari libur nasional tidak auto-populate on fresh install
`hari_liburs` table only gets national holidays via `libur:sync-nasional`, scheduled to run once yearly (Jan 1, plus a Dec 1 pre-sync for next year). A fresh install/dev environment that never had the scheduler running has an empty (or manually-seeded-only) table, which silently means no holidays are excluded from cuti day-count calculations. Run `php artisan libur:sync-nasional --tahun=<year>` manually after seeding a new environment.

Separately: `HariLiburService::hitungHariLibur()` (used by `PengajuanCutiService` and `CutiMassalService` to compute `jumlah_hari`) excludes both weekends (Sabtu/Minggu, via `Carbon::isWeekend()`) and rows in `hari_liburs`. Tests that hit `cuti.store`/`cuti.massal.store` with `now()->addDays(...)` date ranges must freeze time (`$this->travelTo()`) to a known weekday, or the exact `jumlah_hari` assertion becomes flaky depending on which day the suite runs.

## Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal
Karyawan kontrak yang masih di periode pertama (belum pernah diperpanjang — `tipe_karyawan === Kontrak && saldo->periode_ke === 1`, alias "K1" di Laporan Saldo Cuti) belum benar-benar "punya" cuti tahunan. Kalau Cuti Massal membuat saldo mereka kurang, `CutiMassalService::hitungPotongan()` TIDAK membiarkan sisa minus — kuota-nya dinaikkan (bonus) secukupnya supaya sisa jadi tepat 0, dan jumlah bonus itu disimpan di `pengajuan_cutis.bonus_kuota_kontrak_pertama` supaya `batalkan()` bisa menariknya kembali dengan presisi. Karyawan kontrak periode ke-2+ dan karyawan tetap TETAP boleh minus seperti biasa (perilaku lama, sengaja dipertahankan). Saat menguji ini lewat `previewKaryawan()`/route `cuti.massal.create`: acting user HRD lewat `karyawanUser('hrd')` ikut membuat baris Karyawan sendiri yang juga masuk `eligibleKaryawan()` — jangan asumsikan `eligibleKaryawans.0` adalah karyawan yang baru dibuat test; cari berdasarkan `karyawan_id`, atau panggil `CutiMassalService::previewKaryawan()` langsung tanpa lewat HTTP.
