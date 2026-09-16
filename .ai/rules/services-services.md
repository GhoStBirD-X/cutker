---
paths:
  - 'app/Services/SaldoCutiService.php,app/Services/PeriodeCutiService.php'
---

# Services Services

## Periode ke-1 cuti tipe periode = masa kerja minimal, kuota harus 0
Untuk jenis cuti bertipe periode (masa_kerja_minimal_bulan terisi, mis. Cuti Tahunan/Cuti Besar), periode ke-1 merepresentasikan masa kerja minimal yang wajib dipenuhi dulu sesuai UU Ketenagakerjaan — karyawan BELUM berhak cuti selama periode ini, jadi `kuota` dan `sisa` harus 0 (bukan kuota_default) saat dibuat di `SaldoCutiService::bootstrapUntukKaryawanBaru()` dan `generatePeriodeAwalUntukJenisCuti()`.

Kuota penuh baru diberikan mulai periode ke-2 lewat `PeriodeCutiService::lanjutkanPeriode()` saat periode ke-1 ditutup (logic ini TIDAK berubah — sudah benar sejak awal, hanya periode ke-1 yang perlu 0).

Jangan ubah balik ke `kuota_default` di periode ke-1 tanpa konfirmasi user — ini keputusan bisnis yang eksplisit diminta (bukan bug), dikonfirmasi 2026-09-16.
