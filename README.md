# Cuti Kerja Pabrik

Aplikasi manajemen cuti kerja untuk pabrik: pengajuan cuti, approval berjenjang (atasan → HRD), saldo cuti otomatis, jadwal shift, dan laporan.

## Tech Stack

- **Backend**: Laravel 13 (PHP 8.3+), SQLite
- **Frontend**: Inertia.js + React + TypeScript, Tailwind CSS, komponen shadcn/ui
- **Auth**: Laravel Fortify (login, 2FA, passkey)
- **Role & Permission**: spatie/laravel-permission (`karyawan`, `atasan`, `hrd`, `admin`)
- **Export**: maatwebsite/excel (Excel), barryvdh/laravel-dompdf (PDF)
- **Testing**: PHPUnit
- **Dev tooling**: Laravel Boost, Pint, Larastan (PHPStan), ESLint, Prettier

## Arsitektur Singkat

- **Service classes** (`app/Services`) menampung seluruh logika bisnis inti — pengajuan cuti (`PengajuanCutiService`), approval berjenjang & potong saldo (`ApprovalService`), reset saldo tahunan (`SaldoCutiService`). Controller tetap tipis dan hanya memvalidasi (Form Request) lalu memanggil service.
- **Enum PHP native** (`app/Enums`) untuk status: `StatusPengajuan`, `StatusApproval`, `StatusKaryawan`.
- **Alur approval**: karyawan mengajukan → dicek sisa saldo cuti → dibuat `Approval` level 1 untuk atasan langsung (karyawan lain di departemen yang sama dengan role `atasan`) → atasan approve → dibuat `Approval` level 2 untuk HRD → HRD approve (final) → dalam satu `DB::transaction`, saldo cuti dipotong dan status pengajuan berubah jadi `disetujui` secara atomik. Penolakan di level manapun langsung mengubah status pengajuan jadi `ditolak`.
- **Otorisasi**: `PengajuanCutiPolicy` dan `ApprovalPolicy` untuk aksi per-record (lihat, ajukan, batalkan, approve/reject); middleware `role:` di route untuk halaman master data, laporan, dan kelola user.
- **Notifikasi**: `PengajuanCutiDiajukan`, `PengajuanCutiDisetujui`, `PengajuanCutiDitolak` — dikirim lewat channel `database` (muncul di aplikasi) dan `mail`.
- **Reset saldo tahunan**: `php artisan cuti:reset-saldo-tahunan` dijadwalkan tiap 1 Januari (`routes/console.php`), generate `SaldoCuti` baru per karyawan aktif berdasarkan `kuota_default` tiap jenis cuti.
- **Frontend** menggunakan Inertia + React (bukan Blade/Livewire) mengikuti stack project yang sudah ada — halaman ada di `resources/js/pages`, dengan Wayfinder men-generate helper route/action TypeScript otomatis dari route & controller Laravel.

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
- Container `scheduler` menjalankan `php artisan schedule:run` setiap menit (untuk reset saldo tahunan).
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
| HRD | `hrd1@pabrik.test`, `hrd2@pabrik.test` | Approval final, kelola master data, lihat laporan |
| Atasan | `atasan.prd@pabrik.test` | Approval level 1, departemen Produksi |
| Atasan | `atasan.gdg@pabrik.test` | Approval level 1, departemen Gudang |
| Atasan | `atasan.qc@pabrik.test` | Approval level 1, departemen Quality Control |
| Karyawan | `karyawan1@pabrik.test` s/d `karyawan10@pabrik.test` | Ajukan & lihat cuti milik sendiri |

## Alur Uji Coba End-to-End

1. Login sebagai `karyawan1@pabrik.test` → **Ajukan Cuti** → pilih jenis cuti & tanggal → submit.
2. Logout, login sebagai atasan departemen karyawan tersebut → **Approval** → setujui pengajuan.
3. Logout, login sebagai `hrd1@pabrik.test` → **Approval** → setujui final → saldo cuti karyawan otomatis terpotong.
4. Login kembali sebagai karyawan → cek **Dashboard**, saldo berkurang dan notifikasi "disetujui" muncul di navbar.
