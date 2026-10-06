# Graph Report - cutker  (2026-10-06)

## Corpus Check
- 406 files · ~110,624 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 31 file(s) not represented in the graph (top: (none) 19, .stub 6, .example 1)

## Summary
- 2673 nodes · 7561 edges · 140 communities (65 shown, 75 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `be7cc944`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Inertia\Response
- Button
- layout.tsx
- KaryawanFactory.php
- Karyawan
- index.ts
- Card
- @inertiajs/react
- SaldoCutiController
- AlasanCuti
- AuthenticationTest
- app-sidebar-layout.tsx
- Illuminate\Http\RedirectResponse
- react
- Illuminate\Contracts\Validation\ValidationRule
- Illuminate\Http\Request
- HasPerPage
- sidebar.tsx
- package.json
- Illuminate\Foundation\Http\FormRequest
- cn
- SaldoCutiService
- dependencies
- Illuminate\Database\Eloquent\Factories\Factory
- CLAUDE.md
- Laporan/SaldoCutiController.php
- Departemen
- user-menu-content.tsx
- PengajuanCuti
- JadwalShift
- Illuminate\Database\Seeder
- app-sidebar.tsx
- dropdown-menu.tsx
- ApprovalFlowTest
- Illuminate\Database\Schema\Blueprint
- PeriodeCutiService
- Illuminate\Support\Facades\Route
- use-appearance.tsx
- KaryawanController.php
- Illuminate\Database\Eloquent\Relations\BelongsTo
- EmailVerificationTest
- components.json
- Controller
- AppServiceProvider.php
- icon.tsx
- compilerOptions
- Illuminate\Database\Eloquent\Builder
- devDependencies
- KonfirmasiKontrakTest
- PasswordResetTest.php
- scripts
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- app-sidebar-header.tsx
- composer.json
- require
- eslint.config.js
- HariLibur
- require-dev
- SaldoSeverity
- kompensasi/index.tsx
- StatusPengajuanCutiWhatsAppTest
- KaryawanImport.php
- User
- calendar.tsx
- config
- HariLiburController.php
- scripts
- UserController.php
- optionalDependencies
- .buatDataKaryawanLengkap
- JabatanController.php
- psr-4
- laravel
- JadwalShiftPolicy
- StatusPengajuan
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_08_27_141753_add_karyawan_id_to_users_table.php
- 2026_08_28_150006_alter_kuota_default_and_add_masa_kerja_minimal_bulan_to_jenis_cutis_table.php
- ProfileUpdateTest
- 2026_08_28_150009_add_alasan_cuti_id_to_pengajuan_cutis_table.php
- 2026_08_28_154730_add_jam_lembur_and_catatan_lembur_to_jadwal_shifts_table.php
- 2026_09_05_172001_add_jenis_kelamin_dan_tipe_ke_karyawans_table.php
- 2026_09_05_172002_add_khusus_gender_ke_jenis_cutis_table.php
- 2026_09_05_172004_add_periode_dan_ditutup_pada_ke_saldo_cutis_table.php
- 2026_09_05_172008_add_kolom_penyesuaian_manual_ke_saldo_cutis_table.php
- 2026_09_05_172009_add_jumlah_hari_kalender_ke_pengajuan_cutis_table.php
- 2026_09_10_173105_alter_terpakai_dan_sisa_signed_on_saldo_cutis_table.php
- 2026_09_10_173107_add_cuti_massal_id_to_pengajuan_cutis_table.php
- 2026_09_14_021107_add_minimal_hari_pengajuan_ke_jenis_cutis_table.php
- 2026_09_14_021108_add_mendadak_ke_pengajuan_cutis_table.php
- 2026_09_15_053519_add_bonus_kuota_kontrak_pertama_ke_pengajuan_cutis_table.php
- 2026_09_15_063408_make_approver_id_nullable_ke_approvals_table.php
- JenisCutiManagementTest
- 2026_08_27_141750_create_shifts_table.php
- 2026_08_27_141752_create_karyawans_table.php
- 2026_08_27_141754_create_pengajuan_cutis_table.php
- SecurityTest
- 2026_08_27_141757_create_jadwal_shifts_table.php
- 2026_08_27_142636_create_notifications_table.php
- KaryawanRequest.php
- 2026_09_05_172005_create_riwayat_saldo_cutis_table.php
- 2026_09_05_172006_create_kompensasi_cutis_table.php
- 2026_09_05_172007_create_konfirmasi_kontrak_cutis_table.php
- Config
- General
- laravel-boost
- UserFactory
- placeholder-pattern.tsx
- index.md
- VerificationNotificationTest
- master/saldo-cuti.tsx
- StatusCutiMassal
- laporan/saldo-cuti.tsx
- DepartemenFactory
- globe.tsx
- app-header.tsx
- TestCase
- SaldoCutiTest.php
- 0001_01_01_000001_create_cache_table.php
- 2026_08_28_150007_alter_kuota_and_sisa_nullable_on_saldo_cutis_table.php
- 2024_01_01_000000_create_passkeys_table.php
- 2026_08_27_141756_create_approvals_table.php
- 2026_09_05_172003_create_hari_liburs_table.php

## God Nodes (most connected - your core abstractions)
1. `Karyawan` - 188 edges
2. `JenisCuti` - 178 edges
3. `cn()` - 166 edges
4. `SaldoCuti` - 146 edges
5. `User` - 110 edges
6. `Button()` - 99 edges
7. `PengajuanCuti` - 82 edges
8. `react` - 73 edges
9. `TestCase` - 73 edges
10. `@inertiajs/react` - 68 edges

## Surprising Connections (you probably didn't know these)
- `Akun login otomatis dibuat sekalian dengan karyawan` --references--> `KaryawanImportPanduanRoleSheet`  [INFERRED]
  .ai/rules/imports.md → app/Exports/KaryawanImportPanduanRoleSheet.php
- `Arsitektur Singkat` --references--> `HasPerPage`  [INFERRED]
  README.md → app/Http/Controllers/Concerns/HasPerPage.php
- `Arsitektur Singkat` --references--> `PengajuanCutiDiajukan`  [INFERRED]
  README.md → app/Notifications/PengajuanCutiDiajukan.php
- `Arsitektur Singkat` --references--> `PengajuanCutiDisetujui`  [INFERRED]
  README.md → app/Notifications/PengajuanCutiDisetujui.php
- `Arsitektur Singkat` --references--> `PengajuanCutiDitolak`  [INFERRED]
  README.md → app/Notifications/PengajuanCutiDitolak.php

## Import Cycles
- None detected.

## Communities (140 total, 75 thin omitted)

### Community 0 - "Inertia\Response"
Cohesion: 0.08
Nodes (9): StatusKompensasiCuti, CutiMassalController, PengajuanCutiController, StoreCutiMassalRequest, StorePengajuanCutiRequest, TambahKaryawanBaruCutiMassalRequest, CutiMassalService, HariLiburService (+1 more)

### Community 1 - "Button"
Cohesion: 0.10
Nodes (34): InputError(), MasterNav(), PasskeyRegistration(), PasskeyVerify(), Props, PasswordInput(), Props, TextLink() (+26 more)

### Community 2 - "layout.tsx"
Cohesion: 0.14
Nodes (12): @radix-ui/react-separator, Separator(), SidebarSeparator(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn, WhenCurrentUrlFn (+4 more)

### Community 5 - "Karyawan"
Cohesion: 0.04
Nodes (15): CutiMassal, JenisCuti, Karyawan, KonfirmasiKontrakCuti, SaldoCuti, {closure#1}(), SaldoCutiFactory, ResetSaldoCutiTahunanTest (+7 more)

### Community 6 - "index.ts"
Cohesion: 0.12
Nodes (30): Pagination(), PaginationProps, PILIHAN_PER_HALAMAN, terjemahkanLabel(), SaldoCutiInline(), Select(), SelectContent(), SelectItem() (+22 more)

### Community 7 - "Card"
Cohesion: 0.07
Nodes (57): @radix-ui/react-slot, AppLogoFull(), ICONS, SaldoCutiInlineProps, SaldoCutiMeter(), SaldoCutiMeterProps, CLASSES, LABELS (+49 more)

### Community 8 - "@inertiajs/react"
Cohesion: 0.08
Nodes (28): @inertiajs/react, input-otp, Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props (+20 more)

### Community 9 - "SaldoCutiController"
Cohesion: 0.09
Nodes (6): AksiMassalSaldoCuti, {closure#2}(), SaldoCutiController, SaldoCutiAksiMassalRequest, SaldoCutiRequest, SaldoCutiStoreMassalRequest

### Community 10 - "AlasanCuti"
Cohesion: 0.29
Nodes (3): AlasanCuti, AlasanCutiSeeder, {closure#1}()

### Community 12 - "app-sidebar-layout.tsx"
Cohesion: 0.20
Nodes (11): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+3 more)

### Community 13 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.08
Nodes (7): KompensasiCutiController, JadwalShiftController, DepartemenController, NotificationController, StoreJadwalShiftRequest, UpdateLemburRequest, DepartemenRequest

### Community 14 - "react"
Cohesion: 0.10
Nodes (15): @laravel/passkeys, react, Props, buttonVariants, PageProps, STATUS_LABEL, PageProps, PageProps (+7 more)

### Community 15 - "Illuminate\Contracts\Validation\ValidationRule"
Cohesion: 0.07
Nodes (11): EnsureKaryawanLinked, HandleAppearance, {closure#1}(), {closure#1}(), {closure#1}(), BatasWaktuPengajuanCuti, MasaKerjaMencukupi, SesuaiDurasiAlasanCuti (+3 more)

### Community 16 - "Illuminate\Http\Request"
Cohesion: 0.08
Nodes (14): KonfirmasiKontrakController, LaporanCutiController, HandleInertiaRequests, {closure#1}(), {closure#10}(), {closure#11}(), {closure#3}(), {closure#4}() (+6 more)

### Community 17 - "HasPerPage"
Cohesion: 0.09
Nodes (5): HasPerPage, AlasanCutiController, ShiftController, AlasanCutiRequest, ShiftRequest

### Community 18 - "sidebar.tsx"
Cohesion: 0.12
Nodes (34): @radix-ui/react-collapsible, AppSidebar(), NavFooter(), NavMain(), NavUser(), Collapsible(), CollapsibleContent(), CollapsibleTrigger() (+26 more)

### Community 19 - "package.json"
Cohesion: 0.06
Nodes (37): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, eslint, eslint-import-resolver-typescript (+29 more)

### Community 20 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.06
Nodes (11): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, {closure#1}(), SaldoCutiImportRequest, SaldoCutiUpdateMassalRequest, PasswordUpdateRequest (+3 more)

### Community 21 - "cn"
Cohesion: 0.12
Nodes (27): class-variance-authority, @radix-ui/react-toggle, @radix-ui/react-toggle-group, AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants (+19 more)

### Community 22 - "SaldoCutiService"
Cohesion: 0.06
Nodes (19): Periode ke-1 cuti tipe periode = masa kerja minimal, kuota harus 0, Services Services, StatusKaryawan, {closure#3}(), {closure#4}(), {closure#5}(), {closure#7}(), SaldoCutiService (+11 more)

### Community 23 - "dependencies"
Cohesion: 0.05
Nodes (37): dependencies, class-variance-authority, clsx, cobe, concurrently, globals, @inertiajs/react, @inertiajs/vite (+29 more)

### Community 24 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.08
Nodes (9): StatusKonfirmasiKontrak, AlasanCutiFactory, ApprovalFactory, CutiMassalFactory, JenisCutiFactory, {closure#1}(), KompensasiCutiFactory, KonfirmasiKontrakCutiFactory (+1 more)

### Community 25 - "CLAUDE.md"
Cohesion: 0.06
Nodes (33): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+25 more)

### Community 26 - "Laporan/SaldoCutiController.php"
Cohesion: 0.08
Nodes (6): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), SaldoCutiController

### Community 27 - "Departemen"
Cohesion: 0.14
Nodes (5): Departemen, Jabatan, JabatanFactory, KaryawanImportTest, KaryawanManagementTest

### Community 28 - "user-menu-content.tsx"
Cohesion: 0.19
Nodes (12): Avatar(), AvatarFallback(), AvatarImage(), DropdownMenuGroup(), UserInfo(), Props, UserMenuContent(), getInitial() (+4 more)

### Community 29 - "PengajuanCuti"
Cohesion: 0.04
Nodes (20): Notifications, Notifikasi cuti sengaja tidak ShouldQueue, approvals.approver_id nullable — kolam kosong tidak boleh crash, StatusApproval, ApprovalSudahDiprosesException, {closure#1}(), DashboardController, Approval (+12 more)

### Community 30 - "JadwalShift"
Cohesion: 0.09
Nodes (5): JadwalShift, Shift, JadwalShiftFactory, ShiftFactory, JadwalShiftManagementTest

### Community 31 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (9): DatabaseSeeder, {closure#1}(), DepartemenSeeder, {closure#1}(), JabatanSeeder, JenisCutiSeeder, KaryawanSeeder, {closure#1}() (+1 more)

### Community 33 - "dropdown-menu.tsx"
Cohesion: 0.17
Nodes (16): @radix-ui/react-dropdown-menu, NotificationBell(), PageProps, OPTIONS, ThemeToggle(), DropdownMenu(), DropdownMenuCheckboxItem(), DropdownMenuContent() (+8 more)

### Community 35 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.12
Nodes (13): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+5 more)

### Community 36 - "PeriodeCutiService"
Cohesion: 0.18
Nodes (7): RiwayatSaldoCuti, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), PeriodeCutiService

### Community 38 - "use-appearance.tsx"
Cohesion: 0.06
Nodes (42): sonner, withApp(), AppearanceToggleTab(), ThemeColorPicker(), THEMES, Toaster(), TooltipProvider(), Appearance (+34 more)

### Community 39 - "KaryawanController.php"
Cohesion: 0.08
Nodes (7): KaryawanImportTemplateExport, {closure#3}(), KaryawanController, KaryawanImportRequest, KaryawanRequest, ResetDataKaryawanRequest, ResetDataService

### Community 42 - "components.json"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 43 - "Controller"
Cohesion: 0.08
Nodes (5): Controller, JenisCutiController, ProfileController, SecurityController, JenisCutiRequest

### Community 44 - "AppServiceProvider.php"
Cohesion: 0.15
Nodes (3): AppServiceProvider, {closure#2}(), FortifyServiceProvider

### Community 46 - "compilerOptions"
Cohesion: 0.12
Nodes (16): compilerOptions, allowJs, baseUrl, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module (+8 more)

### Community 47 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.06
Nodes (25): Akun login otomatis dibuat sekalian dengan karyawan, Imports, KaryawanImportDataSheet, KaryawanImportPanduanRoleSheet, PengajuanCutiExport, SaldoCutiExport, SaldoCutiMasterExport, {closure#1}() (+17 more)

### Community 48 - "devDependencies"
Cohesion: 0.13
Nodes (15): devDependencies, babel-plugin-react-compiler, eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-react (+7 more)

### Community 51 - "scripts"
Cohesion: 0.15
Nodes (13): scripts, ci:check, dev, lint, lint:check, post-autoload-dump, post-create-project-cmd, post-root-package-install (+5 more)

### Community 52 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 53 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 54 - "app-sidebar-header.tsx"
Cohesion: 0.30
Nodes (11): AppSidebarHeader(), Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList(), BreadcrumbPage() (+3 more)

### Community 55 - "composer.json"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 56 - "require"
Cohesion: 0.17
Nodes (12): require, barryvdh/laravel-dompdf, fakerphp/faker, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker (+4 more)

### Community 57 - "eslint.config.js"
Cohesion: 0.17
Nodes (10): controlStatements, paddingAroundControl, eslint-config-prettier, @eslint/js, eslint-plugin-import, eslint-plugin-react, eslint-plugin-react-hooks, globals (+2 more)

### Community 58 - "HariLibur"
Cohesion: 0.16
Nodes (3): SumberHariLibur, HariLibur, HariLiburFactory

### Community 59 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+2 more)

### Community 60 - "SaldoSeverity"
Cohesion: 0.14
Nodes (4): SaldoSeverity, HasPerPageTest, resolve(), SaldoSeverityTest

### Community 61 - "kompensasi/index.tsx"
Cohesion: 0.21
Nodes (7): Filters, formatRupiah(), Karyawan(), KompensasiCutiIndex(), PageProps, Periode(), rateValid()

### Community 63 - "KaryawanImport.php"
Cohesion: 0.06
Nodes (12): Hari libur nasional tidak auto-populate on fresh install, HRD/Manager mengajukan cuti sendiri skip level di bawah wewenangnya, Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal, Services, Reset Semua Data Karyawan — khusus admin, hapus permanen, HariLiburSync, ProsesSiklusCutiTahunan, ResetSaldoCutiTahunan (+4 more)

### Community 64 - "User"
Cohesion: 0.13
Nodes (3): User, ApprovalPolicy, PengajuanCutiPolicy

### Community 65 - "calendar.tsx"
Cohesion: 0.06
Nodes (33): JadwalShiftCalendar(), NAMA_BULAN, PageProps, parseTanggalLocal(), semuaTanggalDiBulan(), SHIFT_BADGE_CLASS, tanggalHariIni(), weekdayFormatter (+25 more)

### Community 66 - "config"
Cohesion: 0.22
Nodes (9): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, platform, preferred-install, sort-packages (+1 more)

### Community 68 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, build, build:ssr, dev, format, format:check, lint, lint:check (+1 more)

### Community 70 - "optionalDependencies"
Cohesion: 0.25
Nodes (8): optionalDependencies, @laravel/multiplex, lightningcss-linux-x64-gnu, lightningcss-win32-x64-msvc, @rollup/rollup-linux-x64-gnu, @rollup/rollup-win32-x64-msvc, @tailwindcss/oxide-linux-x64-gnu, @tailwindcss/oxide-win32-x64-msvc

### Community 74 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 75 - "laravel"
Cohesion: 0.40
Nodes (5): extra, laravel, post-create-project, dont-discover, installer

### Community 78 - "StatusPengajuan"
Cohesion: 0.08
Nodes (5): StatusPengajuan, CutiMassalSudahDibatalkanException, SaldoCutiTidakCukupException, {closure#1}(), {closure#1}()

### Community 127 - "master/saldo-cuti.tsx"
Cohesion: 0.10
Nodes (48): lucide-react, DeleteUser(), PasskeyItem(), Props, AksiMassalOption, BarisPratinjau, NilaiSaldo, Props (+40 more)

### Community 129 - "laporan/saldo-cuti.tsx"
Cohesion: 0.09
Nodes (11): LemburSummary, PageProps, cariSaldo(), KaryawanRow, KOLOM_JENIS_CUTI, LaporanSaldoCuti(), PageProps, relevanUntukKaryawan() (+3 more)

### Community 132 - "globe.tsx"
Cohesion: 0.50
Nodes (4): cobe, Globe(), MARKERS, warnaTema()

### Community 133 - "app-header.tsx"
Cohesion: 0.17
Nodes (19): @radix-ui/react-dialog, AppHeader(), mainNavItems, Props, rightNavItems, AppLogo(), AppLogoIcon(), Sheet() (+11 more)

### Community 135 - "TestCase"
Cohesion: 0.03
Nodes (37): JenisKelamin, TipeKaryawan, {closure#1}(), {closure#1}(), RoleSeeder, InteractsWithKaryawan, {closure#1}(), PasswordConfirmationTest (+29 more)

## Knowledge Gaps
- **338 isolated node(s):** `php`, `$schema`, `style`, `rsc`, `tsx` (+333 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 864 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **75 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Karyawan` connect `Karyawan` to `KaryawanFactory.php`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `TestCase`, `SaldoCutiController`, `SaldoCutiTest.php`, `AuthenticationTest`, `Illuminate\Http\RedirectResponse`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `SaldoCutiService`, `Illuminate\Database\Eloquent\Factories\Factory`, `Laporan/SaldoCutiController.php`, `Departemen`, `PengajuanCuti`, `JadwalShift`, `Illuminate\Database\Seeder`, `PeriodeCutiService`, `KaryawanController.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Eloquent\Builder`, `KonfirmasiKontrakTest`, `KaryawanImport.php`, `UserController.php`, `.buatDataKaryawanLengkap`, `JadwalShiftPolicy`, `StatusPengajuan`, `JenisCutiManagementTest`, `KaryawanRequest.php`?**
  _High betweenness centrality (0.071) - this node is a cross-community bridge._
- **Why does `JenisCuti` connect `Karyawan` to `Inertia\Response`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `TestCase`, `SaldoCutiController`, `AlasanCuti`, `SaldoCutiTest.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `HasPerPage`, `SaldoCutiService`, `Illuminate\Database\Eloquent\Factories\Factory`, `Laporan/SaldoCutiController.php`, `Departemen`, `PengajuanCuti`, `ApprovalFlowTest`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Controller`, `Illuminate\Database\Eloquent\Builder`, `KonfirmasiKontrakTest`, `StatusPengajuanCutiWhatsAppTest`, `KaryawanImport.php`, `.buatDataKaryawanLengkap`, `StatusPengajuan`, `JenisCutiManagementTest`?**
  _High betweenness centrality (0.053) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `Illuminate\Database\Eloquent\Factories\HasFactory`, `Karyawan`, `TestCase`, `AuthenticationTest`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `Illuminate\Foundation\Http\FormRequest`, `Departemen`, `PengajuanCuti`, `Illuminate\Database\Seeder`, `KaryawanController.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `EmailVerificationTest`, `PasswordResetTest.php`, `StatusPengajuanCutiWhatsAppTest`, `KaryawanImport.php`, `UserController.php`, `.buatDataKaryawanLengkap`, `JadwalShiftPolicy`, `StatusPengajuan`, `ProfileUpdateTest`, `SecurityTest`, `KaryawanRequest.php`, `VerificationNotificationTest`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `style` to the rest of the system?**
  _338 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Inertia\Response` be split into smaller, more focused modules?**
  _Cohesion score 0.0782051282051282 - nodes in this community are weakly interconnected._
- **Should `Button` be split into smaller, more focused modules?**
  _Cohesion score 0.09717514124293786 - nodes in this community are weakly interconnected._
- **Should `layout.tsx` be split into smaller, more focused modules?**
  _Cohesion score 0.1368421052631579 - nodes in this community are weakly interconnected._