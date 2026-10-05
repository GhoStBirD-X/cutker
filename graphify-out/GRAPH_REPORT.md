# Graph Report - cutker  (2026-10-05)

## Corpus Check
- 403 files · ~106,273 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 31 file(s) not represented in the graph (top: (none) 19, .stub 6, .example 1)

## Summary
- 2597 nodes · 7337 edges · 146 communities (71 shown, 75 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `52f8bb3c`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- JenisCuti
- Button
- Master/SaldoCutiController.php
- PengajuanCuti
- TestCase
- auth-card-layout.tsx
- Pagination
- Card
- two-factor-setup-modal.tsx
- use-appearance.tsx
- User
- Illuminate\Http\Request
- Illuminate\Foundation\Http\FormRequest
- KaryawanController.php
- Illuminate\Contracts\Validation\ValidationRule
- Inertia\Response
- Laporan/SaldoCutiController.php
- sidebar.tsx
- package.json
- PengajuanCutiWhatsAppTest.php
- app-header.tsx
- KaryawanImport.php
- dependencies
- Karyawan
- CLAUDE.md
- JabatanFactory
- Departemen
- SaldoSeverity
- StatusCutiMassal
- JadwalShift
- Illuminate\Database\Seeder
- @inertiajs/react
- dropdown-menu.tsx
- Approval
- Illuminate\Database\Schema\Blueprint
- SaldoCutiTest.php
- master.php
- app-sidebar-layout.tsx
- Illuminate\Http\RedirectResponse
- Illuminate\Database\Eloquent\Relations\BelongsTo
- profile.tsx
- components.json
- SaldoCutiService
- AppServiceProvider.php
- lucide-react
- compilerOptions
- SaldoCutiController
- devDependencies
- CutiMassalTest.php
- HariLibur
- scripts
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- Illuminate\Console\Command
- composer.json
- require
- eslint.config.js
- app-sidebar-header.tsx
- require-dev
- ApprovalService.php
- cn
- PeriodeCutiService
- SaldoCutiTest
- JabatanController
- JenisCutiController
- config
- EmailVerificationTest
- scripts
- optionalDependencies
- .buatDataKaryawanLengkap
- KaryawanFactory.php
- psr-4
- laravel
- SecurityController.php
- app.tsx
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_08_27_141753_add_karyawan_id_to_users_table.php
- 2026_08_28_150006_alter_kuota_default_and_add_masa_kerja_minimal_bulan_to_jenis_cutis_table.php
- PasswordResetTest.php
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
- ApprovalController
- 2026_08_27_141750_create_shifts_table.php
- 2026_08_27_141752_create_karyawans_table.php
- 2026_08_27_141754_create_pengajuan_cutis_table.php
- Cuti Kerja Pabrik
- 2026_08_27_141757_create_jadwal_shifts_table.php
- 2026_08_27_142636_create_notifications_table.php
- PasswordResetTest
- 2026_09_05_172005_create_riwayat_saldo_cutis_table.php
- StatusKompensasiCuti
- 2026_09_05_172007_create_konfirmasi_kontrak_cutis_table.php
- Config
- General
- laravel-boost
- icon.tsx
- placeholder-pattern.tsx
- index.md
- layout.tsx
- react
- 0001_01_01_000002_create_jobs_table.php
- laporan/saldo-cuti.tsx
- auth-simple-layout.tsx
- use-theme.tsx
- ProfileUpdateTest
- security.tsx
- AlasanCutiController
- DepartemenController
- SecurityTest
- JenisCutiManagementTest
- vite.config.ts
- alert.tsx
- UserFactory
- 2026_09_05_172010_drop_unique_tahun_dari_saldo_cutis_table.php
- 2026_08_27_141751_create_jenis_cutis_table.php
- 2026_08_28_150008_create_alasan_cutis_table.php
- 2026_09_10_173106_create_cuti_massals_table.php
- ShiftFactory

## God Nodes (most connected - your core abstractions)
1. `Karyawan` - 174 edges
2. `JenisCuti` - 173 edges
3. `cn()` - 164 edges
4. `SaldoCuti` - 139 edges
5. `User` - 108 edges
6. `Button()` - 98 edges
7. `PengajuanCuti` - 77 edges
8. `react` - 72 edges
9. `TestCase` - 71 edges
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

## Communities (146 total, 75 thin omitted)

### Community 0 - "JenisCuti"
Cohesion: 0.06
Nodes (8): JenisCuti, KonfirmasiKontrakCuti, SaldoCuti, PengajuanCutiFormTest, PengajuanCutiTest, PeriodeCutiSiklusTest, SaldoCutiManagementTest, SaldoCutiMassalTest

### Community 1 - "Button"
Cohesion: 0.12
Nodes (25): InputError(), PasskeyRegistration(), Props, PasskeyVerify(), Props, PasswordInput(), Props, TextLink() (+17 more)

### Community 2 - "Master/SaldoCutiController.php"
Cohesion: 0.07
Nodes (15): Akun login otomatis dibuat sekalian dengan karyawan, Imports, KaryawanImportDataSheet, KaryawanImportPanduanRoleSheet, PengajuanCutiExport, SaldoCutiExport, SaldoCutiMasterExport, {closure#4}() (+7 more)

### Community 3 - "PengajuanCuti"
Cohesion: 0.06
Nodes (9): approvals.approver_id nullable — kolam kosong tidak boleh crash, StatusApproval, StatusPengajuan, SaldoCutiTidakCukupException, DashboardController, PengajuanCuti, ApprovalService, ApprovalFactory (+1 more)

### Community 4 - "TestCase"
Cohesion: 0.04
Nodes (28): AlasanCuti, AlasanCutiSeeder, {closure#1}(), RoleSeeder, InteractsWithKaryawan, {closure#1}(), PasswordConfirmationTest, RegistrationTest (+20 more)

### Community 5 - "auth-card-layout.tsx"
Cohesion: 0.33
Nodes (5): AppLogo(), AppLogoIcon(), CardDescription(), AuthCardLayout(), AuthSplitLayout()

### Community 6 - "Pagination"
Cohesion: 0.09
Nodes (34): Pagination(), PaginationProps, PILIHAN_PER_HALAMAN, terjemahkanLabel(), SaldoCutiInline(), Select(), SelectContent(), SelectItem() (+26 more)

### Community 7 - "Card"
Cohesion: 0.07
Nodes (55): ICONS, SaldoCutiInlineProps, SaldoCutiMeter(), SaldoCutiMeterProps, CLASSES, LABELS, StatusBadge(), Props (+47 more)

### Community 8 - "two-factor-setup-modal.tsx"
Cohesion: 0.16
Nodes (17): input-otp, GridScanIcon(), Props, TwoFactorSetupModal(), TwoFactorSetupStep(), TwoFactorVerificationStep(), DialogHeader(), InputOTP (+9 more)

### Community 9 - "use-appearance.tsx"
Cohesion: 0.20
Nodes (19): AppearanceToggleTab(), Appearance, applyTheme(), getServerSystemPrefersDark(), getStoredAppearance(), getSystemPrefersDark(), handleSystemThemeChange(), initializeTheme() (+11 more)

### Community 11 - "User"
Cohesion: 0.11
Nodes (3): User, PengajuanCutiPolicy, AuthenticationTest

### Community 12 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (16): PengajuanCutiController, LaporanCutiController, EnsureKaryawanLinked, HandleAppearance, HandleInertiaRequests, {closure#1}(), {closure#10}(), {closure#11}() (+8 more)

### Community 13 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.06
Nodes (11): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, StoreCutiMassalRequest, TambahKaryawanBaruCutiMassalRequest, SaldoCutiImportRequest, SaldoCutiUpdateMassalRequest (+3 more)

### Community 14 - "KaryawanController.php"
Cohesion: 0.08
Nodes (7): KaryawanImportTemplateExport, {closure#3}(), KaryawanController, KaryawanImportRequest, KaryawanRequest, ResetDataKaryawanRequest, ResetDataService

### Community 15 - "Illuminate\Contracts\Validation\ValidationRule"
Cohesion: 0.06
Nodes (11): {closure#1}(), StorePengajuanCutiRequest, {closure#1}(), {closure#1}(), {closure#1}(), BatasWaktuPengajuanCuti, MasaKerjaMencukupi, SesuaiDurasiAlasanCuti (+3 more)

### Community 16 - "Inertia\Response"
Cohesion: 0.09
Nodes (7): {closure#1}(), HasPerPage, Controller, CutiMassalController, KompensasiCutiController, KonfirmasiKontrakController, ProfileController

### Community 17 - "Laporan/SaldoCutiController.php"
Cohesion: 0.08
Nodes (6): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), SaldoCutiController

### Community 18 - "sidebar.tsx"
Cohesion: 0.15
Nodes (25): @radix-ui/react-collapsible, AppSidebar(), buildNavGroups(), NavFooter(), NavMain(), Collapsible(), CollapsibleContent(), CollapsibleTrigger() (+17 more)

### Community 19 - "package.json"
Cohesion: 0.06
Nodes (32): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, eslint, eslint-import-resolver-typescript (+24 more)

### Community 21 - "app-header.tsx"
Cohesion: 0.18
Nodes (19): @radix-ui/react-dialog, AppHeader(), mainNavItems, Props, rightNavItems, Sheet(), SheetContent(), SheetDescription() (+11 more)

### Community 22 - "KaryawanImport.php"
Cohesion: 0.08
Nodes (9): AksiMassalSaldoCuti, {closure#1}(), {closure#3}(), KaryawanImport, SaldoCutiImport, {closure#3}(), {closure#5}(), {closure#7}() (+1 more)

### Community 23 - "dependencies"
Cohesion: 0.06
Nodes (36): dependencies, class-variance-authority, clsx, concurrently, globals, @inertiajs/react, @inertiajs/vite, input-otp (+28 more)

### Community 24 - "Karyawan"
Cohesion: 0.05
Nodes (15): CutiMassal, Karyawan, {closure#1}(), AlasanCutiFactory, {closure#1}(), CutiMassalFactory, JadwalShiftFactory, JenisCutiFactory (+7 more)

### Community 25 - "CLAUDE.md"
Cohesion: 0.06
Nodes (33): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+25 more)

### Community 27 - "Departemen"
Cohesion: 0.15
Nodes (4): Departemen, Jabatan, KaryawanImportTest, KaryawanManagementTest

### Community 28 - "SaldoSeverity"
Cohesion: 0.14
Nodes (4): SaldoSeverity, HasPerPageTest, resolve(), SaldoSeverityTest

### Community 30 - "JadwalShift"
Cohesion: 0.08
Nodes (5): JadwalShift, Shift, JadwalShiftPolicy, {closure#1}(), JadwalShiftManagementTest

### Community 31 - "Illuminate\Database\Seeder"
Cohesion: 0.08
Nodes (9): DepartemenFactory, DatabaseSeeder, {closure#1}(), DepartemenSeeder, {closure#1}(), JabatanSeeder, JenisCutiSeeder, KaryawanSeeder (+1 more)

### Community 32 - "@inertiajs/react"
Cohesion: 0.07
Nodes (19): @inertiajs/react, NavGroup, MasterNav(), PageProps, PageProps, PageProps, PageProps, emptyForm (+11 more)

### Community 33 - "dropdown-menu.tsx"
Cohesion: 0.10
Nodes (28): @radix-ui/react-dropdown-menu, NavUser(), NotificationBell(), PageProps, OPTIONS, ThemeToggle(), DropdownMenu(), DropdownMenuCheckboxItem() (+20 more)

### Community 34 - "Approval"
Cohesion: 0.16
Nodes (3): Approval, ApprovalPolicy, ApprovalFlowTest

### Community 35 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.12
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}(), {closure#3}() (+4 more)

### Community 38 - "app-sidebar-layout.tsx"
Cohesion: 0.18
Nodes (13): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+5 more)

### Community 39 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.08
Nodes (7): JadwalShiftController, ShiftController, NotificationController, {closure#1}(), StoreJadwalShiftRequest, UpdateLemburRequest, ShiftRequest

### Community 40 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.07
Nodes (3): KompensasiCuti, RiwayatSaldoCuti, KompensasiCutiTest

### Community 41 - "profile.tsx"
Cohesion: 0.31
Nodes (11): Avatar(), AvatarFallback(), AvatarImage(), UserInfo(), getInitial(), GetInitialsFn, useInitials(), PageProps (+3 more)

### Community 42 - "components.json"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 43 - "SaldoCutiService"
Cohesion: 0.06
Nodes (17): Hari libur nasional tidak auto-populate on fresh install, HRD/Manager mengajukan cuti sendiri skip level di bawah wewenangnya, Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal, Services, Reset Semua Data Karyawan — khusus admin, hapus permanen, Periode ke-1 cuti tipe periode = masa kerja minimal, kuota harus 0, Services Services, StatusKaryawan (+9 more)

### Community 44 - "AppServiceProvider.php"
Cohesion: 0.15
Nodes (3): AppServiceProvider, {closure#2}(), FortifyServiceProvider

### Community 45 - "lucide-react"
Cohesion: 0.13
Nodes (15): lucide-react, Checkbox(), formatRupiah(), KompensasiCutiIndex(), KompensasiRow(), PageProps, JadwalShiftCalendar(), NAMA_BULAN (+7 more)

### Community 46 - "compilerOptions"
Cohesion: 0.12
Nodes (16): compilerOptions, allowJs, baseUrl, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module (+8 more)

### Community 47 - "SaldoCutiController"
Cohesion: 0.12
Nodes (4): SaldoCutiController, SaldoCutiAksiMassalRequest, SaldoCutiRequest, SaldoCutiStoreMassalRequest

### Community 48 - "devDependencies"
Cohesion: 0.13
Nodes (15): devDependencies, babel-plugin-react-compiler, eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-react (+7 more)

### Community 50 - "HariLibur"
Cohesion: 0.22
Nodes (3): HariLiburController, HariLiburRequest, HariLibur

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

### Community 58 - "app-sidebar-header.tsx"
Cohesion: 0.29
Nodes (11): @radix-ui/react-slot, AppSidebarHeader(), Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList() (+3 more)

### Community 59 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+2 more)

### Community 60 - "ApprovalService.php"
Cohesion: 0.05
Nodes (13): Notifications, Notifikasi cuti sengaja tidak ShouldQueue, SumberHariLibur, ApprovalSudahDiprosesException, CutiMassalSudahDibatalkanException, WhatsAppChannel, PengajuanCutiDiajukan, PengajuanCutiDisetujui (+5 more)

### Community 61 - "cn"
Cohesion: 0.13
Nodes (23): class-variance-authority, @radix-ui/react-toggle, @radix-ui/react-toggle-group, CardFooter(), NavigationMenu(), NavigationMenuContent(), NavigationMenuIndicator(), NavigationMenuItem() (+15 more)

### Community 62 - "PeriodeCutiService"
Cohesion: 0.18
Nodes (5): StatusKonfirmasiKontrak, {closure#1}(), {closure#2}(), {closure#3}(), PeriodeCutiService

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

### Community 78 - "app.tsx"
Cohesion: 0.27
Nodes (8): sonner, withApp(), Toaster(), TooltipProvider(), useFlashToast(), AppLayout(), AuthLayout(), FlashToast

### Community 101 - "Cuti Kerja Pabrik"
Cohesion: 0.20
Nodes (9): Akun Demo, Alur Uji Coba End-to-End, Cuti Kerja Pabrik, Fungsi Utama, Menjalankan dengan Docker, Menjalankan Secara Lokal, Menjalankan Test, Role & Hak Akses (+1 more)

### Community 126 - "layout.tsx"
Cohesion: 0.15
Nodes (11): @radix-ui/react-separator, Separator(), SidebarSeparator(), IsCurrentOrParentUrlFn, IsCurrentUrlFn, useCurrentUrl(), UseCurrentUrlReturn, WhenCurrentUrlFn (+3 more)

### Community 127 - "react"
Cohesion: 0.11
Nodes (42): react, DeleteUser(), PasskeyItem(), Props, AksiMassalOption, BarisPratinjau, NilaiSaldo, Props (+34 more)

### Community 128 - "0001_01_01_000002_create_jobs_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 129 - "laporan/saldo-cuti.tsx"
Cohesion: 0.05
Nodes (33): LemburSummary, PageProps, cariSaldo(), KaryawanRow, KOLOM_JENIS_CUTI, LaporanSaldoCuti(), PageProps, relevanUntukKaryawan() (+25 more)

### Community 131 - "use-theme.tsx"
Cohesion: 0.18
Nodes (13): ThemeColorPicker(), THEMES, applyTheme(), ColorTheme, getStoredTheme(), initializeTheme(), listeners, notify() (+5 more)

### Community 133 - "security.tsx"
Cohesion: 0.14
Nodes (11): Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props, useTwoFactorAuth(), UseTwoFactorAuthReturn (+3 more)

### Community 138 - "vite.config.ts"
Cohesion: 0.29
Nodes (6): @inertiajs/vite, laravel-vite-plugin, @laravel/vite-plugin-wayfinder, @tailwindcss/vite, vite, @vitejs/plugin-react

### Community 139 - "alert.tsx"
Cohesion: 0.62
Nodes (5): AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants

## Knowledge Gaps
- **335 isolated node(s):** `php`, `$schema`, `style`, `rsc`, `tsx` (+330 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 853 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **75 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Karyawan` connect `Karyawan` to `JenisCuti`, `Master/SaldoCutiController.php`, `PengajuanCuti`, `TestCase`, `JenisCutiManagementTest`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `User`, `Illuminate\Http\Request`, `KaryawanController.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Inertia\Response`, `Laporan/SaldoCutiController.php`, `PengajuanCutiWhatsAppTest.php`, `KaryawanImport.php`, `Departemen`, `JadwalShift`, `Illuminate\Database\Seeder`, `SaldoCutiTest.php`, `master.php`, `Illuminate\Http\RedirectResponse`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `SaldoCutiService`, `SaldoCutiController`, `CutiMassalTest.php`, `ApprovalService.php`, `PeriodeCutiService`, `SaldoCutiTest`, `.buatDataKaryawanLengkap`, `KaryawanFactory.php`?**
  _High betweenness centrality (0.053) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `PengajuanCuti`, `TestCase`, `ProfileUpdateTest`, `SecurityTest`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Http\Request`, `Illuminate\Foundation\Http\FormRequest`, `KaryawanController.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Inertia\Response`, `KaryawanImport.php`, `Karyawan`, `Departemen`, `JadwalShift`, `Illuminate\Database\Seeder`, `Approval`, `master.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `SaldoCutiService`, `ApprovalService.php`, `EmailVerificationTest`, `Illuminate\Support\Facades\Hash`, `.buatDataKaryawanLengkap`, `PasswordResetTest.php`, `PasswordResetTest`?**
  _High betweenness centrality (0.049) - this node is a cross-community bridge._
- **Why does `JenisCuti` connect `JenisCuti` to `Master/SaldoCutiController.php`, `PengajuanCuti`, `TestCase`, `AlasanCutiController`, `JenisCutiManagementTest`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `Illuminate\Http\Request`, `Illuminate\Contracts\Validation\ValidationRule`, `Inertia\Response`, `Laporan/SaldoCutiController.php`, `PengajuanCutiWhatsAppTest.php`, `KaryawanImport.php`, `Karyawan`, `Departemen`, `Approval`, `SaldoCutiTest.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `SaldoCutiService`, `SaldoCutiController`, `CutiMassalTest.php`, `JenisCutiController`, `.buatDataKaryawanLengkap`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `style` to the rest of the system?**
  _335 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `JenisCuti` be split into smaller, more focused modules?**
  _Cohesion score 0.0558641975308642 - nodes in this community are weakly interconnected._
- **Should `Button` be split into smaller, more focused modules?**
  _Cohesion score 0.12 - nodes in this community are weakly interconnected._
- **Should `Master/SaldoCutiController.php` be split into smaller, more focused modules?**
  _Cohesion score 0.0703962703962704 - nodes in this community are weakly interconnected._