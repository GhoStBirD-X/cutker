---
paths:
  - app/Imports/KaryawanImport.php
---

# Imports

## Akun login otomatis dibuat sekalian dengan karyawan
Karyawan & User login tidak lagi dua langkah terpisah untuk kasus umum: (1) form Master Karyawan punya checkbox "Buat akun login sekaligus" (password manual + role dropdown, lihat `KaryawanController::store()`); (2) import CSV SELALU membuat akun login otomatis untuk tiap baris sukses — password di-generate (`Str::password(12)`), role ditentukan dari `App\Support\PetaRoleJabatan::roleUntuk()` (pencocokan PERSIS nama jabatan, bukan substring — default ke role "karyawan" kalau tidak cocok). Kredensial CSV (termasuk password plain text) dikirim SEKALI lewat Inertia flash `importKredensial` lalu hilang — jangan pernah disimpan ke DB/log. Kelola User (`users/index.tsx`) tetap ada untuk kasus khusus: ganti role, reset password, atau bikin akun tanpa profil karyawan. Kalau nambah jabatan baru yang perlu dapat role elevated otomatis, update HANYA `PetaRoleJabatan::PETA` — sheet "Panduan Role" di template import (`KaryawanImportPanduanRoleSheet`) baca dari situ juga jadi otomatis ikut sinkron.
