# Cuti Kerja Pabrik

Aplikasi manajemen cuti karyawan pabrik berbasis web: pengajuan cuti, approval berjenjang 3 level, saldo cuti otomatis, cuti massal, jadwal shift, konfirmasi perpanjangan kontrak, kompensasi cuti (uang pengganti), hari libur nasional, dan laporan.

## Fungsi Utama

- **Pengajuan Cuti** — karyawan mengajukan cuti (pilih jenis cuti, alasan, tanggal mulai/selesai), sistem otomatis menghitung jumlah hari dan memvalidasi sisa saldo. Pengajuan bisa dibatalkan selama masih pending.
- **Approval Berjenjang** — setiap pengajuan melewati 3 level persetujuan berurutan: **Kepala Bagian** (per departemen) → **HRD** → **Manager** (final). Approval final memotong saldo cuti secara atomik. Penolakan di level manapun langsung menutup pengajuan.
- **Saldo Cuti** — tiap karyawan punya saldo cuti per jenis cuti (kuota, terpakai, sisa). Jenis cuti bertipe kalender direset otomatis tiap awal tahun sesuai kuota default. Jenis cuti bertipe periode (mis. Cuti Tahunan, Cuti Besar) mengikuti tanggal masuk kerja masing-masing karyawan — periode ke-1 adalah masa kerja minimal yang wajib dipenuhi dulu (sesuai UU Ketenagakerjaan), jadi kuota/sisanya 0 sampai periode itu selesai; kuota penuh baru diberikan mulai periode ke-2.
- **Cuti Massal** — HRD/Admin membuat cuti bersama (mis. cuti Lebaran) untuk banyak karyawan sekaligus, langsung disetujui tanpa lewat approval berjenjang. Karyawan yang baru masuk kerja setelah sebuah batch dibuat bisa "disusulkan" ke batch yang sama (jenis cuti, tanggal, dan alasan yang identik) dari halaman detail batch, tanpa mengulang form dari awal. Batch bisa dibatalkan, yang otomatis mengembalikan saldo cuti seluruh karyawan terdampak.
- **Konfirmasi Perpanjangan Kontrak** — untuk karyawan kontrak yang periode cutinya akan berakhir, HRD/Admin memproses konfirmasi apakah kontrak diperpanjang atau tidak. Kalau diperpanjang, HRD wajib mengisi tanggal akhir kontrak baru (harus setelah tanggal periode yang ditutup — mendukung konfirmasi yang telat diproses/menunggak) yang otomatis memperbarui data kontrak karyawan, sekaligus melanjutkan siklus cuti berikutnya.
- **Kompensasi Cuti** — pengajuan uang pengganti cuti yang tidak terpakai (dibuat otomatis saat periode cuti ditutup dengan sisa > 0); HRD/Admin bisa memproses satu per satu atau memilih banyak sekaligus (bulk) dengan satu rate per hari yang sama, sehingga total rupiah kompensasi terhitung otomatis untuk semua yang dipilih.
- **Jadwal Shift** — kalender shift kerja karyawan per departemen, termasuk pencatatan lembur; dikelola oleh HRD, Admin, atau Koordinator Shift di departemennya masing-masing. Mendukung hapus massal (pilih beberapa jadwal pada satu tanggal sekaligus lalu hapus) selain hapus satu per satu.
- **Hari Libur** — daftar hari libur (nasional & internal pabrik) yang disinkronkan otomatis dari kalender nasional Indonesia setiap tahun, dipakai sebagai acuan penghitungan hari kerja/cuti.
- **Master Data** — HRD/Admin mengelola data karyawan, departemen, jabatan, jenis cuti, alasan cuti, shift, hari libur, dan saldo cuti. Semua daftar berpaginasi punya selektor jumlah baris per halaman (10/20/30/50).
- **Laporan** — rekap data cuti yang bisa diekspor ke Excel dan PDF.
- **Manajemen User & Role** — Admin mengelola akun pengguna dan hak akses (role) lewat spatie/laravel-permission.
- **Notifikasi** — notifikasi in-app (lonceng di navbar) dan email untuk pengajuan baru, disetujui, dan ditolak.

## Role & Hak Akses

| Role | Cakupan |
| --- | --- |
| `karyawan` | Ajukan cuti & lihat riwayat cuti milik sendiri |
| `kepala_bagian` | Approval level 1, khusus bawahan di departemennya |
| `hrd` | Approval level 2, kelola master data, jadwal shift, laporan, konfirmasi kontrak, kompensasi cuti |
| `manager` | Approval level 3 (final), lihat laporan |
| `koordinator_shift` | Kelola jadwal shift departemennya sendiri |
| `admin` | Akses penuh: semua hak di atas + kelola user & role |

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3+), SQLite
- **Frontend**: Inertia.js + React + TypeScript, Tailwind CSS, komponen shadcn/ui
- **Auth**: Laravel Fortify (login, 2FA, passkey)
- **Role & Permission**: spatie/laravel-permission
- **Export**: maatwebsite/excel (Excel), barryvdh/laravel-dompdf (PDF)
- **Testing**: PHPUnit
- **Dev tooling**: Laravel Boost, Pint, Larastan (PHPStan), ESLint, Prettier

## Arsitektur Singkat

- **Service classes** (`app/Services`) menampung seluruh logika bisnis inti — pengajuan cuti (`PengajuanCutiService`), approval berjenjang & potong saldo (`ApprovalService`), reset saldo tahunan & bootstrap saldo karyawan baru (`SaldoCutiService`), siklus periode cuti/konfirmasi kontrak (`PeriodeCutiService`), cuti massal (`CutiMassalService`), sinkronisasi hari libur nasional (`HariLiburService`). Controller tetap tipis dan hanya memvalidasi (Form Request) lalu memanggil service.
- **Selektor jumlah baris per halaman**: trait `HasPerPage` (`app/Http/Controllers/Concerns`) dipakai di semua controller `index()` yang berpaginasi, membatasi query string `per_page` ke pilihan yang tersedia (10/20/30/50) agar tidak disalahgunakan.
- **Enum PHP native** (`app/Enums`) untuk status: `StatusPengajuan`, `StatusApproval`, `StatusKaryawan`, `StatusKonfirmasiKontrak`, `StatusKompensasiCuti`, dll.
- **Alur approval**: karyawan mengajukan → dicek sisa saldo cuti → dibuat `Approval` level 1 untuk Kepala Bagian di departemen yang sama → disetujui → level 2 untuk HRD → disetujui → level 3 untuk Manager (final) → dalam satu `DB::transaction`, saldo cuti dipotong dan status pengajuan berubah jadi `disetujui` secara atomik. Penolakan di level manapun langsung mengubah status pengajuan jadi `ditolak`. Pemilihan approver HRD/Manager otomatis memilih yang beban approval pending-nya paling sedikit, agar merata.
- **Otorisasi**: Policy (`PengajuanCutiPolicy`, `ApprovalPolicy`) untuk aksi per-record; middleware `role:` di route untuk halaman master data, laporan, jadwal shift, dan kelola user; middleware `karyawan.linked` memastikan user sudah terhubung ke data karyawan sebelum mengakses fitur cuti.
- **Notifikasi**: `PengajuanCutiDiajukan`, `PengajuanCutiDisetujui`, `PengajuanCutiDitolak` — dikirim lewat channel `database` (muncul di aplikasi) dan `mail`.
- **Job terjadwal** (`routes/console.php`):
  - `cuti:reset-saldo-tahunan` — tiap 1 Januari, generate saldo cuti baru per karyawan aktif berdasarkan kuota default tiap jenis cuti.
  - `cuti:proses-siklus-tahunan` — harian, menutup periode cuti tahunan/besar yang sudah lewat berdasarkan tanggal masuk kerja tiap karyawan.
  - `libur:sync-nasional` — tiap 1 Januari (dan 1 Desember untuk tahun berikutnya), sinkronisasi hari libur nasional Indonesia.
- **Frontend** menggunakan Inertia + React (bukan Blade/Livewire) — halaman ada di `resources/js/pages`, dengan Wayfinder men-generate helper route/action TypeScript otomatis dari route & controller Laravel.

## Menjalankan Secara Lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
composer run dev   # menjalankan server, queue worker, log tail, dan Vite bersamaan
```

Aplikasi berjalan di `http://localhost:8000`.

## Menjalankan dengan Docker

```bash
cp .env.example .env
docker compose up --build
```

- Aplikasi bisa diakses di `http://localhost:8080` (nginx).
- Container `app` otomatis menjalankan migrasi dan `storage:link` saat start.
- Container `scheduler` menjalankan `php artisan schedule:run` setiap menit (untuk reset saldo tahunan, siklus cuti, dan sinkronisasi hari libur).
- Database SQLite dan file `storage/` disimpan di Docker volume agar persisten antar restart.
- Jalankan seeder data demo secara manual sekali setelah container pertama kali naik:

```bash
docker compose exec app php artisan db:seed --force
```

## Menjalankan Test

```bash
php artisan test
vendor/bin/pint          # code style
vendor/bin/phpstan analyse # static analysis
npm run lint:check && npm run types:check
```

## Akun Demo

Semua akun demo memakai password: **`password`**

| Role | Email | Keterangan |
| --- | --- | --- |
| Admin | `admin@pabrik.test` | Akses penuh, kelola user & role |
| Manager | `manager@pabrik.test` | Approval level 3 (final), company-wide |
| HRD | `hrd1@pabrik.test`, `hrd2@pabrik.test` | Approval level 2, kelola master data & laporan |
| Kepala Bagian | `kabag.prd@pabrik.test`, `kabag.gdg@pabrik.test`, `kabag.qc@pabrik.test`, `kabag.cls@pabrik.test` | Approval level 1, satu per departemen (Produksi, Gudang, QC, Cleaning Service) |
| Koordinator Shift | `koordinator.prd@pabrik.test`, `koordinator.gdg@pabrik.test`, `koordinator.qc@pabrik.test`, `koordinator.cls@pabrik.test` | Kelola jadwal shift departemennya |
| Karyawan | `karyawan1@pabrik.test` s/d `karyawan10@pabrik.test` | Ajukan & lihat cuti milik sendiri |

## Alur Uji Coba End-to-End

1. Login sebagai `karyawan1@pabrik.test` → **Ajukan Cuti** → pilih jenis cuti & tanggal → submit.
2. Logout, login sebagai Kepala Bagian departemen karyawan tersebut (mis. `kabag.prd@pabrik.test`) → **Approval** → setujui pengajuan (level 1).
3. Logout, login sebagai `hrd1@pabrik.test` → **Approval** → setujui (level 2, diteruskan ke Manager).
4. Logout, login sebagai `manager@pabrik.test` → **Approval** → setujui final → saldo cuti karyawan otomatis terpotong.
5. Login kembali sebagai karyawan → cek **Dashboard**, saldo berkurang dan notifikasi "disetujui" muncul di navbar.
