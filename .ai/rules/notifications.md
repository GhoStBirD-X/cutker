---
paths:
  - 'app/Notifications/*.php'
---

# Notifications

## Notifikasi cuti sengaja tidak ShouldQueue
`PengajuanCutiDiajukan`, `PengajuanCutiDisetujui`, `PengajuanCutiDitolak` sengaja TIDAK implement `ShouldQueue`. Sebelumnya queued, dan di server produksi/lokal yang hanya punya systemd service untuk scheduler (bukan queue worker), notifikasi diam-diam tidak pernah terkirim/tercatat — approver tidak pernah tahu ada pengajuan cuti baru, tanpa error apa pun. App ini kecil (HR internal, low-traffic), jadi dispatch sinkron (1 insert DB + 1 mail via MAIL_MAILER) lebih aman daripada butuh infrastruktur tambahan yang gampang lupa dijalankan. Jangan tambahkan `ShouldQueue` lagi ke sini kecuali sudah pasti ada queue worker permanen (systemd/supervisor) di semua environment yang menjalankan app ini.
