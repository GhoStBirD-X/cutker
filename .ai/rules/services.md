---
paths:
  - app/Services/HariLiburService.php
---

# Services

## Hari libur nasional tidak auto-populate on fresh install
`hari_liburs` table only gets national holidays via `libur:sync-nasional`, scheduled to run once yearly (Jan 1, plus a Dec 1 pre-sync for next year). A fresh install/dev environment that never had the scheduler running has an empty (or manually-seeded-only) table, which silently means no holidays are excluded from cuti day-count calculations. Run `php artisan libur:sync-nasional --tahun=<year>` manually after seeding a new environment.

Separately: `HariLiburService::hitungHariLibur()` (used by `PengajuanCutiService` and `CutiMassalService` to compute `jumlah_hari`) excludes both weekends (Sabtu/Minggu, via `Carbon::isWeekend()`) and rows in `hari_liburs`. Tests that hit `cuti.store`/`cuti.massal.store` with `now()->addDays(...)` date ranges must freeze time (`$this->travelTo()`) to a known weekday, or the exact `jumlah_hari` assertion becomes flaky depending on which day the suite runs.
