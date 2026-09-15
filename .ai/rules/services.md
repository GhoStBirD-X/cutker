---
paths:
  - app/Services/HariLiburService.php
  - app/Services/CutiMassalService.php
  - app/Services/ApprovalService.php
---

# Services

## Hari libur nasional tidak auto-populate on fresh install
`hari_liburs` table only gets national holidays via `libur:sync-nasional`, scheduled to run once yearly (Jan 1, plus a Dec 1 pre-sync for next year). A fresh install/dev environment that never had the scheduler running has an empty (or manually-seeded-only) table, which silently means no holidays are excluded from cuti day-count calculations. Run `php artisan libur:sync-nasional --tahun=<year>` manually after seeding a new environment.

Separately: `HariLiburService::hitungHariLibur()` (used by `PengajuanCutiService` and `CutiMassalService` to compute `jumlah_hari`) excludes both weekends (Sabtu/Minggu, via `Carbon::isWeekend()`) and rows in `hari_liburs`. Tests that hit `cuti.store`/`cuti.massal.store` with `now()->addDays(...)` date ranges must freeze time (`$this->travelTo()`) to a known weekday, or the exact `jumlah_hari` assertion becomes flaky depending on which day the suite runs.

## Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal
Karyawan kontrak yang masih di periode pertama (belum pernah diperpanjang — `tipe_karyawan === Kontrak && saldo->periode_ke === 1`, alias "K1" di Laporan Saldo Cuti) belum benar-benar "punya" cuti tahunan. Kalau Cuti Massal membuat saldo mereka kurang, `CutiMassalService::hitungPotongan()` TIDAK membiarkan sisa minus — kuota-nya dinaikkan (bonus) secukupnya supaya sisa jadi tepat 0, dan jumlah bonus itu disimpan di `pengajuan_cutis.bonus_kuota_kontrak_pertama` supaya `batalkan()` bisa menariknya kembali dengan presisi. Karyawan kontrak periode ke-2+ dan karyawan tetap TETAP boleh minus seperti biasa (perilaku lama, sengaja dipertahankan). Saat menguji ini lewat `previewKaryawan()`/route `cuti.massal.create`: acting user HRD lewat `karyawanUser('hrd')` ikut membuat baris Karyawan sendiri yang juga masuk `eligibleKaryawan()` — jangan asumsikan `eligibleKaryawans.0` adalah karyawan yang baru dibuat test; cari berdasarkan `karyawan_id`, atau panggil `CutiMassalService::previewKaryawan()` langsung tanpa lewat HTTP.

## HRD/Manager mengajukan cuti sendiri skip level di bawah wewenangnya
`ApprovalService::mulaiAlur()` (dipanggil dari `PengajuanCutiService::ajukan()`, bukan bikin Approval level 1 langsung) menyesuaikan titik mulai alur approval dengan role si pengaju sendiri: Manager mengajukan cuti untuk dirinya sendiri → auto-approved seketika (tidak ada level di atas Manager, tidak ada baris Approval sama sekali). HRD mengajukan untuk dirinya sendiri → skip Kepala Bagian & HRD, langsung 1 Approval di level Manager. Selain itu (karyawan biasa, Kepala Bagian) → alur normal 3 level seperti biasa. Ini keputusan produk yang eksplisit diminta user, bukan bug — jangan dikembalikan ke alur 3-level seragam.

## approvals.approver_id nullable — kolam kosong tidak boleh crash
`approvals.approver_id` nullable (migrasi `make_approver_id_nullable_ke_approvals_table`). Level HRD & Manager itu kolam bersama per role (lihat `teruskan()`/`ApprovalController::index()`/`ApprovalPolicy`) — kalau role tujuan belum punya siapa pun saat approval dibuat, `approver_id` boleh null dan approval tetap dibuat pending; begitu ada user baru dengan role itu, mereka otomatis melihat & bisa memproses approval itu lewat query berbasis level+role yang sudah ada (tidak perlu backfill approver_id). Level Kepala Bagian BUKAN kolam bersama (terikat satu departemen) — kalau departemen belum punya kepala_bagian, `mulaiAlur()` melewati level itu sepenuhnya dan mulai dari HRD, bukan membuat approval level 1 dengan approver_id null (karena tidak ada mekanisme "kolam" untuk menjemputnya nanti). Jangan kembalikan `approver_id` ke NOT NULL tanpa menghapus juga jalur null di `teruskan()`/`mulaiAlur()`.
