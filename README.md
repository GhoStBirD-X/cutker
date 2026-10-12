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
- **Notifikasi** — notifikasi in-app (lonceng di navbar), email, dan **WhatsApp** (lewat Evolution API) untuk pengajuan baru, diteruskan, disetujui, dan ditolak. Pesan WhatsApp yang gagal terkirim (mis. instance WhatsApp terputus) disimpan di antrian dan dikirim ulang otomatis begitu tersambung kembali.

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

- **Service classes** (`app/Services`) menampung seluruh logika bisnis inti — pengajuan cuti (`PengajuanCutiService`), approval berjenjang & potong saldo (`ApprovalService`), reset saldo tahunan & bootstrap saldo karyawan baru (`SaldoCutiService`), siklus periode cuti/konfirmasi kontrak (`PeriodeCutiService`), cuti massal (`CutiMassalService`), sinkronisasi hari libur nasional (`HariLiburService`), pengiriman & antrian WhatsApp (`AntrianWhatsappService`). Controller tetap tipis dan hanya memvalidasi (Form Request) lalu memanggil service.
- **Selektor jumlah baris per halaman**: trait `HasPerPage` (`app/Http/Controllers/Concerns`) dipakai di semua controller `index()` yang berpaginasi, membatasi query string `per_page` ke pilihan yang tersedia (10/20/30/50) agar tidak disalahgunakan.
- **Enum PHP native** (`app/Enums`) untuk status: `StatusPengajuan`, `StatusApproval`, `StatusKaryawan`, `StatusKonfirmasiKontrak`, `StatusKompensasiCuti`, dll.
- **Alur approval**: karyawan mengajukan → dicek sisa saldo cuti → dibuat `Approval` level 1 untuk Kepala Bagian di departemen yang sama → disetujui → level 2 untuk HRD → disetujui → level 3 untuk Manager (final) → dalam satu `DB::transaction`, saldo cuti dipotong dan status pengajuan berubah jadi `disetujui` secara atomik. Penolakan di level manapun langsung mengubah status pengajuan jadi `ditolak`. Pemilihan approver HRD/Manager otomatis memilih yang beban approval pending-nya paling sedikit, agar merata.
- **Otorisasi**: Policy (`PengajuanCutiPolicy`, `ApprovalPolicy`) untuk aksi per-record; middleware `role:` di route untuk halaman master data, laporan, jadwal shift, dan kelola user; middleware `karyawan.linked` memastikan user sudah terhubung ke data karyawan sebelum mengakses fitur cuti.
- **Notifikasi**: `PengajuanCutiDiajukan`, `PengajuanCutiDiteruskan`, `PengajuanCutiDisetujui`, `PengajuanCutiDitolak` — dikirim lewat channel `database` (muncul di aplikasi), `mail`, dan `WhatsAppChannel`. Notifikasi sengaja dikirim sinkron (tanpa queue worker); setiap pesan WhatsApp dicatat di tabel `antrian_whatsapps` dan yang gagal dikirim ulang oleh scheduler (lihat [Artisan Command](#artisan-command)).
- **Job terjadwal** (`routes/console.php`): reset saldo tahunan, siklus periode cuti, sinkronisasi hari libur, dan antrian WhatsApp — detail lengkap di [Artisan Command](#artisan-command).
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
php artisan libur:sync-nasional   # isi hari libur nasional tahun berjalan
composer run dev   # menjalankan server, queue worker, log tail, dan Vite bersamaan
```

Job terjadwal (siklus cuti, antrian WhatsApp, dll.) **tidak** ikut berjalan lewat `composer run dev`. Jalankan scheduler di terminal terpisah bila diperlukan:

```bash
php artisan schedule:work
```

Aplikasi berjalan di `http://localhost:8000`.

## Menjalankan dengan Docker

```bash
cp .env.example .env
docker compose up --build
```

- Aplikasi bisa diakses di `http://localhost:8080` (nginx).
- Container `app` otomatis menjalankan migrasi dan `storage:link` saat start.
- Container `scheduler` menjalankan `php artisan schedule:run` setiap menit (untuk reset saldo tahunan, siklus cuti, sinkronisasi hari libur, dan pengiriman ulang antrian WhatsApp).
- Container `evolution-api` (+ `evolution-db`) adalah gateway WhatsApp di port `8081` — lihat [Notifikasi WhatsApp](#notifikasi-whatsapp-evolution-api).
- Database SQLite dan file `storage/` disimpan di Docker volume agar persisten antar restart.
- Jalankan seeder data demo secara manual sekali setelah container pertama kali naik:

```bash
docker compose exec app php artisan db:seed --force
```

## Notifikasi WhatsApp (Evolution API)

Notifikasi WhatsApp dikirim lewat [Evolution API](https://github.com/evolution-foundation/evolution-api) yang berjalan sebagai container Docker. Konfigurasi di `.env`:

```env
EVOLUTION_BASE_URL=http://localhost:8081   # alamat Evolution API, tanpa "/" di akhir
EVOLUTION_INSTANCE=cutker                  # nama instance (huruf besar-kecil berpengaruh)
EVOLUTION_API_KEY=                          # API key Evolution (AUTHENTICATION_API_KEY di docker-compose)
EVOLUTION_DB_PASSWORD=                      # password Postgres milik Evolution
```

Nomor HP penerima diambil dari kolom `no_hp` data karyawan (format `08…` otomatis diubah ke `628…`). Karyawan tanpa nomor HP tetap menerima notifikasi in-app & email.

**Cara kerja antrian:**

1. Saat notifikasi dibuat, pesan langsung dicoba dikirim sekali, lalu dicatat di tabel `antrian_whatsapps` dengan status `terkirim` (berhasil) atau `pending` (gagal).
2. Scheduler menjalankan `whatsapp:kirim-antrian` setiap menit. Pesan `pending` hanya dikirim bila instance berstatus `open`, dengan jeda acak 2–5 detik antar pesan (maks. 50 per putaran) agar nomor tidak dibatasi WhatsApp.
3. Bila koneksi putus lagi di tengah pengiriman, proses berhenti dan dilanjutkan di putaran berikutnya.
4. Pesan yang 10 kali gagal **saat instance tersambung** ditandai `gagal` dan tidak dicoba lagi.

**Batasan:** Evolution membalas sukses (HTTP 201) sebelum WhatsApp benar-benar menerima pesan. Bila WhatsApp kemudian menolak (mis. error `463` — nomor dibatasi sementara karena dicurigai bot), pesan tetap tercatat `terkirim`. Untuk kasus ini gunakan `whatsapp:kirim-ulang-ditolak` (lihat di bawah).

**Memantau antrian:**

```bash
php artisan tinker --execute 'echo App\Models\AntrianWhatsapp::query()->selectRaw("status, count(*) as jumlah")->groupBy("status")->get();'
```

## Artisan Command

Command khusus aplikasi ini (di luar command bawaan Laravel). Daftar lengkap semua command: `php artisan list`; bantuan per command: `php artisan <command> --help`.

### Ringkasan

| Command | Fungsi | Jadwal otomatis |
| --- | --- | --- |
| `cuti:reset-saldo-tahunan` | Generate saldo cuti jenis kalender untuk tahun baru | 1 Januari, 00:00 |
| `cuti:proses-siklus-tahunan` | Tutup periode cuti tahunan/besar yang sudah lewat | Setiap hari, 00:00 |
| `kontrak:tinjau-siklus` | Masukkan karyawan kontrak yang telanjur melewati K5 ke Konfirmasi Kontrak | Manual |
| `libur:sync-nasional` | Sinkronkan hari libur nasional Indonesia | 1 Januari (tahun berjalan) & 1 Desember (tahun berikutnya) |
| `whatsapp:kirim-antrian` | Kirim ulang pesan WhatsApp yang tertunda | Setiap menit |
| `whatsapp:kirim-ulang-ditolak` | Antrikan ulang notifikasi yang ditolak WhatsApp di suatu instance | Manual |

Lihat semua jadwal beserta waktu jalan berikutnya:

```bash
php artisan schedule:list
```

### Cuti & Kontrak

#### `cuti:reset-saldo-tahunan`

```bash
php artisan cuti:reset-saldo-tahunan [--tahun=2027]
```

Membuat saldo cuti baru untuk setiap karyawan aktif pada **jenis cuti bertipe kalender** (jenis cuti tanpa masa kerja minimal), dengan kuota sesuai `kuota_default` jenis cuti. Saldo tahun sebelumnya ditutup.

- `--tahun` — tahun saldo yang dibuat, default tahun berjalan.
- **Aman dijalankan ulang**: karyawan yang sudah punya saldo di tahun tersebut dilewati.
- Jenis cuti bertipe periode (Cuti Tahunan, Cuti Besar) **tidak** diproses di sini — lihat `cuti:proses-siklus-tahunan`.

#### `cuti:proses-siklus-tahunan`

```bash
php artisan cuti:proses-siklus-tahunan
```

Menutup setiap periode cuti bertipe periode (Cuti Tahunan, Cuti Besar) yang tanggal selesainya sudah lewat, berdasarkan `tanggal_masuk` masing-masing karyawan:

- Periode yang ditutup diarsipkan ke riwayat saldo; sisa cuti > 0 otomatis dibuatkan pengajuan **Kompensasi Cuti**.
- **Karyawan tetap** — langsung dilanjutkan ke periode berikutnya.
- **Karyawan kontrak** — dibuatkan **Konfirmasi Kontrak** berstatus menunggu, untuk diputuskan HRD (diperpanjang / tidak).
- Diulang sampai tidak ada periode yang tertinggal, jadi bila scheduler sempat mati beberapa hari, satu kali jalan sudah mengejar semuanya.

#### `kontrak:tinjau-siklus`

```bash
php artisan kontrak:tinjau-siklus --simulasi   # tampilkan daftar saja
php artisan kontrak:tinjau-siklus              # terapkan
```

Mencari karyawan kontrak yang telanjur melewati K5 (periode ke-6 dst., biasanya data lama sebelum aturan siklus K1–K5 berlaku) dan memasukkannya ke daftar **Konfirmasi Kontrak**. HRD kemudian memutuskan: angkat tetap, kontrak ulang ke K1 (wajib alasan), atau tidak diperpanjang.

- `--simulasi` — hanya menampilkan tabel karyawan (NPK, nama, periode, posisi K, kuota, sisa) tanpa membuat konfirmasi. **Jalankan ini dulu.**
- Tidak dijadwalkan; cukup dijalankan sekali saat dibutuhkan (mis. setelah impor data karyawan lama).

### Hari Libur

#### `libur:sync-nasional`

```bash
php artisan libur:sync-nasional [--tahun=2027]
```

Mengambil kalender hari libur nasional Indonesia dari sumber publik dan menyimpannya ke tabel `hari_liburs`. Hari libur dipakai untuk mengecualikan tanggal merah dari penghitungan jumlah hari cuti.

- `--tahun` — tahun yang disinkronkan, default tahun berjalan.
- **Aman dijalankan ulang**: tanggal yang sudah ada diperbarui, bukan diduplikasi.
- Bila sumber tidak bisa diakses, command tidak error (hasil 0 tanggal); hari libur tetap bisa diisi manual lewat **Master Data → Hari Libur**.
- **Wajib dijalankan manual** setelah setup environment baru, karena jadwal otomatisnya hanya setahun sekali — tanpa ini tabel kosong dan hari libur ikut terhitung sebagai hari cuti.

### WhatsApp

#### `whatsapp:kirim-antrian`

```bash
php artisan whatsapp:kirim-antrian
```

Mengirim ulang pesan berstatus `pending` di `antrian_whatsapps` (lihat [cara kerja antrian](#notifikasi-whatsapp-evolution-api)). Berjalan otomatis setiap menit; jalankan manual bila ingin langsung mengirim tanpa menunggu.

- Output `Instance WhatsApp belum tersambung, antrian tidak diproses.` — instance belum `open`; cek di Evolution Manager.
- Output `N pesan WhatsApp dari antrian berhasil terkirim.` — selesai (0 berarti tidak ada yang tertunda).

#### `whatsapp:kirim-ulang-ditolak`

```bash
php artisan whatsapp:kirim-ulang-ditolak KKP --sejak=2026-10-12           # tampilkan daftar saja
php artisan whatsapp:kirim-ulang-ditolak KKP --sejak=2026-10-12 --simpan  # masukkan ke antrian
php artisan whatsapp:kirim-antrian                                         # kirim sekarang
```

Dipakai saat sebuah instance **ditolak/dibatasi WhatsApp** (pesan berstatus `ERROR` di Evolution, mis. error `463`) dan Anda sudah pindah ke instance/nomor lain. Command membaca riwayat pesan keluar instance tersebut di Evolution, lalu memasukkan ulang notifikasi yang ditolak ke antrian agar dikirim lewat instance yang aktif di `.env`.

- `instance` (wajib) — nama instance tempat pesan ditolak (instance lama).
- `--sejak=Y-m-d` — hanya pesan sejak tanggal ini, agar notifikasi lama yang sudah tidak relevan tidak ikut terkirim.
- `--simpan` — benar-benar memasukkan ke antrian. Tanpa opsi ini hanya menampilkan tabel (waktu, nomor, judul pesan). **Periksa daftarnya dulu.**
- Hanya notifikasi cutker yang diambil (teks diawali `Pengajuan Cuti …` / `Update Pengajuan Cuti`); pesan tes manual diabaikan, dan pesan identik ke nomor yang sama hanya diambil sekali.

### Menjalankan Scheduler

Semua jadwal di atas hanya berjalan bila `php artisan schedule:run` dipanggil setiap menit:

| Environment | Cara |
| --- | --- |
| Server (systemd) | service `cutker-scheduler` — cek dengan `systemctl status cutker-scheduler` |
| Docker Compose | container `scheduler` (otomatis) |
| Lokal / development | `php artisan schedule:work` di terminal terpisah |

### Setelah Update Kode di Server

```bash
git pull
php artisan migrate --force          # bila ada migration baru
php artisan optimize:clear
sudo systemctl restart cutker cutker-scheduler
php artisan schedule:list            # pastikan jadwal terdaftar
```

> Jalankan `migrate` sebagai user yang sama dengan service aplikasi agar kepemilikan file SQLite tidak berubah. Bila mengubah `.env` (mis. ganti `EVOLUTION_INSTANCE`), jalankan `optimize:clear` dan restart service agar nilai baru terbaca.

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
