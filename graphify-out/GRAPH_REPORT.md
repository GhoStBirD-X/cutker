# Graph Report - cutker  (2026-10-06)

## Corpus Check
- 404 files · ~109,413 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 31 file(s) not represented in the graph (top: (none) 19, .stub 6, .example 1)

## Summary
- 2637 nodes · 7437 edges · 128 communities (67 shown, 61 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `ce8fcd32`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- JenisCuti
- Button
- SaldoCutiMasterExport
- KaryawanFactory
- TestCase
- Illuminate\Database\Eloquent\Factories\Factory
- ajukan.tsx
- dashboard.tsx
- two-factor-setup-modal.tsx
- Card
- Illuminate\Database\Eloquent\Relations\HasMany
- User
- app-sidebar-layout.tsx
- Illuminate\Foundation\Http\FormRequest
- DepartemenController.php
- Illuminate\Contracts\Validation\ValidationRule
- Illuminate\Http\Request
- AlasanCutiController.php
- sidebar.tsx
- package.json
- PasswordValidationRules
- cn
- SaldoCutiService
- dependencies
- Karyawan
- CLAUDE.md
- Laporan/SaldoCutiController.php
- Departemen
- JenisKelamin
- PengajuanCutiDiajukan
- JadwalShift
- Illuminate\Database\Seeder
- app-sidebar.tsx
- dropdown-menu.tsx
- Approval
- Illuminate\Database\Schema\Blueprint
- useIsMobile
- master.php
- use-appearance.tsx
- Illuminate\Http\RedirectResponse
- Illuminate\Database\Eloquent\Relations\BelongsTo
- master/saldo-cuti.tsx
- components.json
- Inertia\Response
- KaryawanRequest.php
- icon.tsx
- compilerOptions
- Illuminate\Database\Eloquent\Builder
- devDependencies
- KonfirmasiKontrakTest
- scripts
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- Illuminate\Console\Command
- composer.json
- require
- eslint.config.js
- breadcrumbs.tsx
- require-dev
- KonfirmasiKontrakCuti
- KaryawanImport.php
- calendar.tsx
- config
- SaldoCutiTest.php
- scripts
- UserFactory.php
- optionalDependencies
- .buatDataKaryawanLengkap
- psr-4
- laravel
- PengajuanCuti
- PengajuanCutiWhatsAppTest
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_08_27_141753_add_karyawan_id_to_users_table.php
- 2026_08_28_150006_alter_kuota_default_and_add_masa_kerja_minimal_bulan_to_jenis_cutis_table.php
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
- 2026_08_27_141750_create_shifts_table.php
- 2026_08_27_141752_create_karyawans_table.php
- 2026_08_27_141754_create_pengajuan_cutis_table.php
- Cuti Kerja Pabrik
- 2026_08_27_141757_create_jadwal_shifts_table.php
- 2026_08_27_142636_create_notifications_table.php
- 2026_09_05_172005_create_riwayat_saldo_cutis_table.php
- 2026_09_05_172006_create_kompensasi_cutis_table.php
- 2026_09_05_172007_create_konfirmasi_kontrak_cutis_table.php
- Config
- General
- laravel-boost
- placeholder-pattern.tsx
- index.md
- utils.ts
- react
- laporan/saldo-cuti.tsx
- app-header.tsx
- Inertia\Testing\AssertableInertia
- Illuminate\Foundation\Testing\RefreshDatabase
- 0001_01_01_000001_create_cache_table.php
- 2026_08_28_150007_alter_kuota_and_sisa_nullable_on_saldo_cutis_table.php
- 2024_01_01_000000_create_passkeys_table.php
- 2026_08_27_141756_create_approvals_table.php
- 2026_09_05_172003_create_hari_liburs_table.php

## God Nodes (most connected - your core abstractions)
1. `Karyawan` - 177 edges
2. `JenisCuti` - 172 edges
3. `cn()` - 166 edges
4. `SaldoCuti` - 143 edges
5. `User` - 108 edges
6. `Button()` - 99 edges
7. `PengajuanCuti` - 77 edges
8. `react` - 73 edges
9. `TestCase` - 71 edges
10. `@inertiajs/react` - 68 edges

## Surprising Connections (you probably didn't know these)
- `Arsitektur Singkat` --references--> `StatusKaryawan`  [INFERRED]
  README.md → app/Enums/StatusKaryawan.php
- `Akun login otomatis dibuat sekalian dengan karyawan` --references--> `KaryawanImportPanduanRoleSheet`  [INFERRED]
  .ai/rules/imports.md → app/Exports/KaryawanImportPanduanRoleSheet.php
- `Arsitektur Singkat` --references--> `HasPerPage`  [INFERRED]
  README.md → app/Http/Controllers/Concerns/HasPerPage.php
- `Arsitektur Singkat` --references--> `PengajuanCutiDiajukan`  [INFERRED]
  README.md → app/Notifications/PengajuanCutiDiajukan.php
- `Notifikasi cuti sengaja tidak ShouldQueue` --references--> `PengajuanCutiDisetujui`  [INFERRED]
  .ai/rules/notifications.md → app/Notifications/PengajuanCutiDisetujui.php

## Import Cycles
- None detected.

## Communities (128 total, 61 thin omitted)

### Community 0 - "JenisCuti"
Cohesion: 0.06
Nodes (7): JenisCuti, SaldoCuti, PengajuanCutiTest, PeriodeCutiSiklusTest, SaldoCutiManagementTest, {closure#2}(), SaldoCutiMassalTest

### Community 1 - "Button"
Cohesion: 0.07
Nodes (35): @inertiajs/react, @laravel/passkeys, Heading(), InputError(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor() (+27 more)

### Community 2 - "SaldoCutiMasterExport"
Cohesion: 0.06
Nodes (13): Akun login otomatis dibuat sekalian dengan karyawan, Imports, KaryawanImportDataSheet, KaryawanImportPanduanRoleSheet, KaryawanImportTemplateExport, PengajuanCutiExport, SaldoCutiExport, SaldoCutiMasterExport (+5 more)

### Community 4 - "TestCase"
Cohesion: 0.09
Nodes (4): RegistrationTest, VerificationNotificationTest, TestCase, ExampleTest

### Community 5 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.05
Nodes (14): HariLibur, ApprovalFactory, CutiMassalFactory, DepartemenFactory, HariLiburFactory, JabatanFactory, JadwalShiftFactory, JenisCutiFactory (+6 more)

### Community 6 - "ajukan.tsx"
Cohesion: 0.09
Nodes (32): PaginationProps, PILIHAN_PER_HALAMAN, terjemahkanLabel(), Select(), SelectContent(), SelectItem(), SelectLabel(), SelectScrollDownButton() (+24 more)

### Community 7 - "dashboard.tsx"
Cohesion: 0.07
Nodes (39): AppLogoIcon(), ICONS, SaldoCutiInline(), SaldoCutiInlineProps, SaldoCutiMeter(), SaldoCutiMeterProps, CLASSES, LABELS (+31 more)

### Community 8 - "two-factor-setup-modal.tsx"
Cohesion: 0.13
Nodes (18): input-otp, GridScanIcon(), Props, TwoFactorSetupModal(), TwoFactorSetupStep(), TwoFactorVerificationStep(), DialogHeader(), InputOTP (+10 more)

### Community 9 - "Card"
Cohesion: 0.10
Nodes (38): MasterNav(), Pagination(), Card(), CardContent(), Checkbox(), Input(), Label(), formatDate() (+30 more)

### Community 10 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.08
Nodes (4): AlasanCuti, AlasanCutiFactory, AlasanCutiSeeder, {closure#1}()

### Community 11 - "User"
Cohesion: 0.05
Nodes (8): User, JadwalShiftPolicy, PengajuanCutiPolicy, AuthenticationTest, EmailVerificationTest, PasswordResetTest, ProfileUpdateTest, SecurityTest

### Community 12 - "app-sidebar-layout.tsx"
Cohesion: 0.17
Nodes (12): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+4 more)

### Community 13 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.05
Nodes (10): SecurityController, StoreCutiMassalRequest, TambahKaryawanBaruCutiMassalRequest, {closure#1}(), SaldoCutiImportRequest, {closure#1}(), SaldoCutiStoreMassalRequest, SaldoCutiUpdateMassalRequest (+2 more)

### Community 15 - "Illuminate\Contracts\Validation\ValidationRule"
Cohesion: 0.07
Nodes (11): EnsureKaryawanLinked, HandleAppearance, {closure#1}(), StorePengajuanCutiRequest, {closure#1}(), BatasWaktuPengajuanCuti, MasaKerjaMencukupi, SesuaiDurasiAlasanCuti (+3 more)

### Community 16 - "Illuminate\Http\Request"
Cohesion: 0.07
Nodes (14): LaporanCutiController, SaldoCutiController, HandleInertiaRequests, {closure#1}(), {closure#10}(), {closure#11}(), {closure#3}(), {closure#4}() (+6 more)

### Community 18 - "sidebar.tsx"
Cohesion: 0.14
Nodes (28): @radix-ui/react-collapsible, AppSidebar(), NavFooter(), NavMain(), Collapsible(), CollapsibleContent(), CollapsibleTrigger(), SidebarContent() (+20 more)

### Community 19 - "package.json"
Cohesion: 0.05
Nodes (39): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, eslint, eslint-import-resolver-typescript (+31 more)

### Community 20 - "PasswordValidationRules"
Cohesion: 0.12
Nodes (6): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, ProfileDeleteRequest, ProfileUpdateRequest

### Community 21 - "cn"
Cohesion: 0.10
Nodes (32): class-variance-authority, @radix-ui/react-navigation-menu, @radix-ui/react-toggle, @radix-ui/react-toggle-group, AlertError(), Alert(), AlertDescription(), AlertTitle() (+24 more)

### Community 22 - "SaldoCutiService"
Cohesion: 0.06
Nodes (21): approvals.approver_id nullable — kolam kosong tidak boleh crash, Hari libur nasional tidak auto-populate on fresh install, HRD/Manager mengajukan cuti sendiri skip level di bawah wewenangnya, Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal, Services, Reset Semua Data Karyawan — khusus admin, hapus permanen, Periode ke-1 cuti tipe periode = masa kerja minimal, kuota harus 0, Services Services (+13 more)

### Community 23 - "dependencies"
Cohesion: 0.05
Nodes (37): dependencies, class-variance-authority, clsx, cobe, concurrently, globals, @inertiajs/react, @inertiajs/vite (+29 more)

### Community 24 - "Karyawan"
Cohesion: 0.06
Nodes (8): {closure#2}(), {closure#4}(), CutiMassal, Karyawan, {closure#1}(), ResetSaldoCutiTahunanTest, CutiMassalTest, SaldoCutiTest

### Community 25 - "CLAUDE.md"
Cohesion: 0.06
Nodes (33): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+25 more)

### Community 26 - "Laporan/SaldoCutiController.php"
Cohesion: 0.05
Nodes (9): {closure#10}(), {closure#5}(), {closure#6}(), {closure#3}(), KaryawanController, KaryawanImportRequest, KaryawanRequest, ResetDataKaryawanRequest (+1 more)

### Community 27 - "Departemen"
Cohesion: 0.13
Nodes (4): Departemen, Jabatan, KaryawanImportTest, KaryawanManagementTest

### Community 29 - "PengajuanCutiDiajukan"
Cohesion: 0.08
Nodes (7): Notifications, Notifikasi cuti sengaja tidak ShouldQueue, SumberHariLibur, WhatsAppChannel, PengajuanCutiDiajukan, PengajuanCutiDitolak, {closure#3}()

### Community 30 - "JadwalShift"
Cohesion: 0.07
Nodes (6): JadwalShiftController, StoreJadwalShiftRequest, UpdateLemburRequest, JadwalShift, Shift, JadwalShiftManagementTest

### Community 31 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (10): DatabaseSeeder, {closure#1}(), DepartemenSeeder, {closure#1}(), JabatanSeeder, {closure#1}(), JenisCutiSeeder, KaryawanSeeder (+2 more)

### Community 32 - "app-sidebar.tsx"
Cohesion: 0.09
Nodes (10): buildNavGroups(), NavGroup, PageProps, PageProps, PageProps, ROLES, UserRow, Shift (+2 more)

### Community 33 - "dropdown-menu.tsx"
Cohesion: 0.14
Nodes (19): AppSidebarHeader(), NavUser(), NotificationBell(), PageProps, OPTIONS, ThemeToggle(), DropdownMenu(), DropdownMenuContent() (+11 more)

### Community 34 - "Approval"
Cohesion: 0.14
Nodes (3): Approval, ApprovalPolicy, ApprovalFlowTest

### Community 35 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.12
Nodes (13): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+5 more)

### Community 36 - "useIsMobile"
Cohesion: 0.70
Nodes (4): getServerSnapshot(), isSmallerThanBreakpoint(), mediaQueryListener(), useIsMobile()

### Community 38 - "use-appearance.tsx"
Cohesion: 0.05
Nodes (50): cobe, sonner, withApp(), AppLogoFull(), AppearanceToggleTab(), Globe(), MARKERS, warnaTema() (+42 more)

### Community 39 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.05
Nodes (15): ApprovalController, {closure#1}(), HasPerPage, Controller, KompensasiCutiController, KonfirmasiKontrakController, HariLiburController, JenisCutiController (+7 more)

### Community 41 - "master/saldo-cuti.tsx"
Cohesion: 0.16
Nodes (19): AksiMassalOption, SheetContent(), SheetDescription(), SheetFooter(), SheetHeader(), SheetOverlay(), SheetPortal(), SheetTitle() (+11 more)

### Community 42 - "components.json"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 43 - "Inertia\Response"
Cohesion: 0.10
Nodes (5): StatusKompensasiCuti, CutiMassalController, PengajuanCutiController, UserController, UserRequest

### Community 44 - "KaryawanRequest.php"
Cohesion: 0.10
Nodes (4): {closure#1}(), AppServiceProvider, {closure#2}(), FortifyServiceProvider

### Community 46 - "compilerOptions"
Cohesion: 0.12
Nodes (16): compilerOptions, allowJs, baseUrl, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module (+8 more)

### Community 47 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.07
Nodes (18): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#1}() (+10 more)

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

### Community 54 - "Illuminate\Console\Command"
Cohesion: 0.27
Nodes (3): HariLiburSync, ProsesSiklusCutiTahunan, ResetSaldoCutiTahunan

### Community 55 - "composer.json"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, minimum-stability, name, prefer-stable (+3 more)

### Community 56 - "require"
Cohesion: 0.17
Nodes (12): require, barryvdh/laravel-dompdf, fakerphp/faker, inertiajs/inertia-laravel, laravel/chisel, laravel/fortify, laravel/framework, laravel/tinker (+4 more)

### Community 57 - "eslint.config.js"
Cohesion: 0.17
Nodes (10): controlStatements, paddingAroundControl, eslint-config-prettier, @eslint/js, eslint-plugin-import, eslint-plugin-react, eslint-plugin-react-hooks, globals (+2 more)

### Community 58 - "breadcrumbs.tsx"
Cohesion: 0.40
Nodes (9): @radix-ui/react-slot, Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList(), BreadcrumbPage() (+1 more)

### Community 59 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+2 more)

### Community 62 - "KonfirmasiKontrakCuti"
Cohesion: 0.07
Nodes (11): StatusKaryawan, StatusKonfirmasiKontrak, TipeKaryawan, KonfirmasiKontrakCuti, RiwayatSaldoCuti, {closure#1}(), {closure#2}(), {closure#3}() (+3 more)

### Community 63 - "KaryawanImport.php"
Cohesion: 0.09
Nodes (6): AksiMassalSaldoCuti, {closure#2}(), {closure#3}(), KaryawanImport, SaldoCutiImport, PetaRoleJabatan

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

### Community 76 - "PengajuanCuti"
Cohesion: 0.04
Nodes (12): StatusApproval, StatusCutiMassal, StatusPengajuan, ApprovalSudahDiprosesException, CutiMassalSudahDibatalkanException, SaldoCutiTidakCukupException, DashboardController, PengajuanCuti (+4 more)

### Community 101 - "Cuti Kerja Pabrik"
Cohesion: 0.20
Nodes (9): Akun Demo, Alur Uji Coba End-to-End, Cuti Kerja Pabrik, Fungsi Utama, Menjalankan dengan Docker, Menjalankan Secara Lokal, Menjalankan Test, Role & Hak Akses (+1 more)

### Community 126 - "utils.ts"
Cohesion: 0.23
Nodes (9): Separator(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn, WhenCurrentUrlFn, SettingsLayout(), sidebarNavItems (+1 more)

### Community 127 - "react"
Cohesion: 0.13
Nodes (38): lucide-react, react, DeleteUser(), PasskeyItem(), Props, BarisPratinjau, NilaiSaldo, Props (+30 more)

### Community 129 - "laporan/saldo-cuti.tsx"
Cohesion: 0.10
Nodes (9): LemburSummary, PageProps, cariSaldo(), KaryawanRow, KOLOM_JENIS_CUTI, PageProps, relevanUntukKaryawan(), PageProps (+1 more)

### Community 133 - "app-header.tsx"
Cohesion: 0.21
Nodes (17): AppHeader(), mainNavItems, Props, rightNavItems, AppLogo(), Avatar(), AvatarFallback(), AvatarImage() (+9 more)

### Community 135 - "Inertia\Testing\AssertableInertia"
Cohesion: 0.08
Nodes (18): {closure#1}(), PasswordConfirmationTest, {closure#1}(), TwoFactorChallengeTest, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}() (+10 more)

### Community 137 - "Illuminate\Foundation\Testing\RefreshDatabase"
Cohesion: 0.05
Nodes (10): RoleSeeder, InteractsWithKaryawan, PengajuanCutiFormTest, EnsureKaryawanLinkedTest, ExampleTest, AlasanCutiManagementTest, HariLiburManagementTest, JenisCutiManagementTest (+2 more)

## Knowledge Gaps
- **338 isolated node(s):** `php`, `$schema`, `style`, `rsc`, `tsx` (+333 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 863 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **61 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Karyawan` connect `Karyawan` to `JenisCuti`, `TestCase`, `Illuminate\Database\Eloquent\Factories\Factory`, `Inertia\Testing\AssertableInertia`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Illuminate\Database\Eloquent\Relations\HasMany`, `User`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `SaldoCutiService`, `Laporan/SaldoCutiController.php`, `Departemen`, `JenisKelamin`, `JadwalShift`, `Illuminate\Database\Seeder`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Inertia\Response`, `KaryawanRequest.php`, `Illuminate\Database\Eloquent\Builder`, `KonfirmasiKontrakTest`, `KonfirmasiKontrakCuti`, `KaryawanImport.php`, `SaldoCutiTest.php`, `.buatDataKaryawanLengkap`, `PengajuanCuti`?**
  _High betweenness centrality (0.052) - this node is a cross-community bridge._
- **Why does `JenisCuti` connect `JenisCuti` to `Illuminate\Database\Eloquent\Factories\Factory`, `Inertia\Testing\AssertableInertia`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Illuminate\Foundation\Http\FormRequest`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `AlasanCutiController.php`, `SaldoCutiService`, `Karyawan`, `Laporan/SaldoCutiController.php`, `Departemen`, `JenisKelamin`, `Illuminate\Database\Seeder`, `Approval`, `Illuminate\Http\RedirectResponse`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Inertia\Response`, `Illuminate\Database\Eloquent\Builder`, `KonfirmasiKontrakTest`, `KonfirmasiKontrakCuti`, `SaldoCutiTest.php`, `.buatDataKaryawanLengkap`, `PengajuanCuti`, `PengajuanCutiWhatsAppTest`?**
  _High betweenness centrality (0.044) - this node is a cross-community bridge._
- **Why does `SaldoCuti` connect `JenisCuti` to `SaldoCutiMasterExport`, `Illuminate\Database\Eloquent\Factories\Factory`, `Inertia\Testing\AssertableInertia`, `Illuminate\Foundation\Testing\RefreshDatabase`, `Illuminate\Contracts\Validation\ValidationRule`, `SaldoCutiService`, `Karyawan`, `Laporan/SaldoCutiController.php`, `Departemen`, `JenisKelamin`, `Approval`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Inertia\Response`, `Illuminate\Database\Eloquent\Builder`, `KonfirmasiKontrakTest`, `KonfirmasiKontrakCuti`, `KaryawanImport.php`, `SaldoCutiTest.php`, `.buatDataKaryawanLengkap`, `PengajuanCuti`, `PengajuanCutiWhatsAppTest`?**
  _High betweenness centrality (0.036) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `style` to the rest of the system?**
  _338 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `JenisCuti` be split into smaller, more focused modules?**
  _Cohesion score 0.05835010060362173 - nodes in this community are weakly interconnected._
- **Should `Button` be split into smaller, more focused modules?**
  _Cohesion score 0.07333333333333333 - nodes in this community are weakly interconnected._
- **Should `SaldoCutiMasterExport` be split into smaller, more focused modules?**
  _Cohesion score 0.05639097744360902 - nodes in this community are weakly interconnected._