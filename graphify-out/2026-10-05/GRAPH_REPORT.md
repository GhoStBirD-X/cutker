# Graph Report - cutker  (2026-10-05)

## Corpus Check
- 391 files · ~99,185 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 31 file(s) not represented in the graph (top: (none) 19, .stub 6, .example 1)

## Summary
- 2485 nodes · 6909 edges · 126 communities (64 shown, 62 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `cbe7adea`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Karyawan
- Button
- Illuminate\Database\Eloquent\Builder
- PengajuanCuti
- TestCase
- Card
- Label
- index.ts
- two-factor-setup-modal.tsx
- use-appearance.tsx
- Illuminate\Database\Eloquent\Relations\BelongsTo
- User
- Illuminate\Http\Request
- Illuminate\Foundation\Http\FormRequest
- Laporan/SaldoCutiController.php
- Illuminate\Contracts\Validation\ValidationRule
- Inertia\Response
- Illuminate\Http\RedirectResponse
- sidebar.tsx
- package.json
- InteractsWithKaryawan
- cn
- KaryawanImport.php
- dependencies
- KompensasiCuti
- CLAUDE.md
- Illuminate\Database\Eloquent\Factories\Factory
- Departemen
- lucide-react
- CutiMassalService.php
- JadwalShift
- Illuminate\Database\Seeder
- app-sidebar.tsx
- dropdown-menu.tsx
- Approval
- Illuminate\Database\Schema\Blueprint
- SaldoCutiTest.php
- Illuminate\Support\Facades\Route
- app-sidebar-layout.tsx
- JadwalShiftController.php
- RiwayatSaldoCuti
- user-menu-content.tsx
- components.json
- SaldoCutiService
- AppServiceProvider.php
- calendar.tsx
- compilerOptions
- Master/SaldoCutiController.php
- devDependencies
- KonfirmasiKontrakCuti
- HariLibur
- scripts
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- Illuminate\Console\Command
- composer.json
- require
- eslint.config.js
- breadcrumbs.tsx
- require-dev
- Cuti Kerja Pabrik
- toggle-group.tsx
- konfirmasi-kontrak/index.tsx
- SumberHariLibur
- JabatanController
- JenisCutiController
- config
- PengajuanCutiWhatsAppTest.php
- scripts
- UserFactory.php
- optionalDependencies
- .buatDataKaryawanLengkap
- KaryawanFactory
- psr-4
- laravel
- 0001_01_01_000001_create_cache_table.php
- 2025_08_14_170933_add_two_factor_columns_to_users_table.php
- 2026_08_27_141753_add_karyawan_id_to_users_table.php
- 2026_08_28_150006_alter_kuota_default_and_add_masa_kerja_minimal_bulan_to_jenis_cutis_table.php
- 2026_08_28_150007_alter_kuota_and_sisa_nullable_on_saldo_cutis_table.php
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
- 2024_01_01_000000_create_passkeys_table.php
- 2026_08_27_141750_create_shifts_table.php
- 2026_08_27_141752_create_karyawans_table.php
- 2026_08_27_141754_create_pengajuan_cutis_table.php
- 2026_08_27_141756_create_approvals_table.php
- 2026_08_27_141757_create_jadwal_shifts_table.php
- 2026_08_27_142636_create_notifications_table.php
- 2026_09_05_172003_create_hari_liburs_table.php
- 2026_09_05_172005_create_riwayat_saldo_cutis_table.php
- 2026_09_05_172006_create_kompensasi_cutis_table.php
- 2026_09_05_172007_create_konfirmasi_kontrak_cutis_table.php
- Config
- General
- laravel-boost
- icon.tsx
- placeholder-pattern.tsx
- index.md

## God Nodes (most connected - your core abstractions)
1. `Karyawan` - 164 edges
2. `JenisCuti` - 162 edges
3. `cn()` - 156 edges
4. `SaldoCuti` - 122 edges
5. `User` - 108 edges
6. `Button()` - 92 edges
7. `PengajuanCuti` - 77 edges
8. `TestCase` - 69 edges
9. `react` - 68 edges
10. `@inertiajs/react` - 65 edges

## Surprising Connections (you probably didn't know these)
- `Arsitektur Singkat` --references--> `StatusKaryawan`  [INFERRED]
  README.md → app/Enums/StatusKaryawan.php
- `Akun login otomatis dibuat sekalian dengan karyawan` --references--> `KaryawanImportPanduanRoleSheet`  [INFERRED]
  .ai/rules/imports.md → app/Exports/KaryawanImportPanduanRoleSheet.php
- `Arsitektur Singkat` --references--> `HasPerPage`  [INFERRED]
  README.md → app/Http/Controllers/Concerns/HasPerPage.php
- `approvals.approver_id nullable — kolam kosong tidak boleh crash` --references--> `ApprovalPolicy`  [INFERRED]
  .ai/rules/services.md → app/Policies/ApprovalPolicy.php
- `Arsitektur Singkat` --references--> `ApprovalPolicy`  [INFERRED]
  README.md → app/Policies/ApprovalPolicy.php

## Import Cycles
- None detected.

## Communities (126 total, 62 thin omitted)

### Community 0 - "Karyawan"
Cohesion: 0.05
Nodes (13): {closure#2}(), {closure#4}(), CutiMassal, JenisCuti, Karyawan, SaldoCuti, {closure#1}(), ResetSaldoCutiTahunanTest (+5 more)

### Community 1 - "Button"
Cohesion: 0.07
Nodes (34): @inertiajs/react, react, Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props (+26 more)

### Community 2 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.05
Nodes (13): Akun login otomatis dibuat sekalian dengan karyawan, Imports, KaryawanImportDataSheet, KaryawanImportPanduanRoleSheet, KaryawanImportTemplateExport, PengajuanCutiExport, SaldoCutiExport, {closure#1}() (+5 more)

### Community 3 - "PengajuanCuti"
Cohesion: 0.05
Nodes (19): Notifications, Notifikasi cuti sengaja tidak ShouldQueue, approvals.approver_id nullable — kolam kosong tidak boleh crash, StatusPengajuan, ApprovalSudahDiprosesException, PengajuanCuti, WhatsAppChannel, PengajuanCutiDiajukan (+11 more)

### Community 4 - "TestCase"
Cohesion: 0.05
Nodes (21): {closure#1}(), PasswordConfirmationTest, RegistrationTest, {closure#1}(), TwoFactorChallengeTest, VerificationNotificationTest, {closure#1}(), {closure#1}() (+13 more)

### Community 5 - "Card"
Cohesion: 0.09
Nodes (46): ICONS, SaldoCutiInlineProps, SaldoCutiMeter(), SaldoCutiMeterProps, StatusBadge(), Props, TwoFactorRecoveryCodes(), Badge() (+38 more)

### Community 6 - "Label"
Cohesion: 0.10
Nodes (48): InputError(), MasterNav(), Pagination(), PaginationProps, PILIHAN_PER_HALAMAN, terjemahkanLabel(), SaldoCutiInline(), Input() (+40 more)

### Community 7 - "index.ts"
Cohesion: 0.05
Nodes (36): CLASSES, LABELS, LemburSummary, PageProps, cariSaldo(), KaryawanRow, KOLOM_JENIS_CUTI, LaporanSaldoCuti() (+28 more)

### Community 8 - "two-factor-setup-modal.tsx"
Cohesion: 0.07
Nodes (46): input-otp, AlertError(), DeleteUser(), PasskeyItem(), Props, GridScanIcon(), Props, TwoFactorSetupModal() (+38 more)

### Community 9 - "use-appearance.tsx"
Cohesion: 0.06
Nodes (44): sonner, withApp(), AppLogoFull(), AppearanceToggleTab(), ThemeColorPicker(), THEMES, Toaster(), TooltipProvider() (+36 more)

### Community 10 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.07
Nodes (3): AlasanCuti, AlasanCutiSeeder, {closure#1}()

### Community 11 - "User"
Cohesion: 0.05
Nodes (8): User, JadwalShiftPolicy, PengajuanCutiPolicy, AuthenticationTest, EmailVerificationTest, PasswordResetTest, ProfileUpdateTest, SecurityTest

### Community 12 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (16): LaporanCutiController, SaldoCutiController, EnsureKaryawanLinked, HandleAppearance, HandleInertiaRequests, {closure#1}(), {closure#10}(), {closure#11}() (+8 more)

### Community 13 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.05
Nodes (12): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, ProfileController, SecurityController, StoreCutiMassalRequest, TambahKaryawanBaruCutiMassalRequest (+4 more)

### Community 14 - "Laporan/SaldoCutiController.php"
Cohesion: 0.05
Nodes (9): {closure#10}(), {closure#5}(), {closure#6}(), {closure#3}(), KaryawanController, KaryawanImportRequest, KaryawanRequest, ResetDataKaryawanRequest (+1 more)

### Community 15 - "Illuminate\Contracts\Validation\ValidationRule"
Cohesion: 0.08
Nodes (10): {closure#1}(), StorePengajuanCutiRequest, {closure#1}(), {closure#2}(), BatasWaktuPengajuanCuti, MasaKerjaMencukupi, SesuaiDurasiAlasanCuti, SesuaiGenderJenisCuti (+2 more)

### Community 16 - "Inertia\Response"
Cohesion: 0.09
Nodes (7): {closure#1}(), HasPerPage, Controller, CutiMassalController, KompensasiCutiController, KonfirmasiKontrakController, PengajuanCutiController

### Community 17 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.06
Nodes (9): AlasanCutiController, DepartemenController, ShiftController, NotificationController, UserController, AlasanCutiRequest, DepartemenRequest, ShiftRequest (+1 more)

### Community 18 - "sidebar.tsx"
Cohesion: 0.10
Nodes (36): NavFooter(), NavMain(), NavUser(), Collapsible(), CollapsibleContent(), CollapsibleTrigger(), SidebarContext, SidebarGroup() (+28 more)

### Community 19 - "package.json"
Cohesion: 0.05
Nodes (40): private, $schema, type, babel-plugin-react-compiler, clsx, concurrently, eslint, eslint-import-resolver-typescript (+32 more)

### Community 20 - "InteractsWithKaryawan"
Cohesion: 0.06
Nodes (6): RoleSeeder, InteractsWithKaryawan, EnsureKaryawanLinkedTest, HariLiburManagementTest, JenisCutiManagementTest, UserManagementTest

### Community 21 - "cn"
Cohesion: 0.13
Nodes (32): AppHeader(), mainNavItems, Props, rightNavItems, AppLogo(), AppLogoIcon(), CardFooter(), NavigationMenu() (+24 more)

### Community 22 - "KaryawanImport.php"
Cohesion: 0.08
Nodes (6): JenisKelamin, StatusKaryawan, TipeKaryawan, {closure#3}(), KaryawanImport, PetaRoleJabatan

### Community 23 - "dependencies"
Cohesion: 0.06
Nodes (36): dependencies, class-variance-authority, clsx, concurrently, globals, @inertiajs/react, @inertiajs/vite, input-otp (+28 more)

### Community 24 - "KompensasiCuti"
Cohesion: 0.07
Nodes (8): StatusApproval, StatusKompensasiCuti, StatusKonfirmasiKontrak, DashboardController, KompensasiCuti, {closure#1}(), KompensasiCutiFactory, KompensasiCutiTest

### Community 25 - "CLAUDE.md"
Cohesion: 0.06
Nodes (33): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+25 more)

### Community 26 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.07
Nodes (11): AlasanCutiFactory, ApprovalFactory, CutiMassalFactory, DepartemenFactory, HariLiburFactory, JabatanFactory, JenisCutiFactory, KonfirmasiKontrakCutiFactory (+3 more)

### Community 27 - "Departemen"
Cohesion: 0.13
Nodes (4): Departemen, Jabatan, KaryawanImportTest, KaryawanManagementTest

### Community 28 - "lucide-react"
Cohesion: 0.10
Nodes (13): lucide-react, badgeVariants, Checkbox(), formatRupiah(), KompensasiCutiIndex(), KompensasiRow(), PageProps, PageProps (+5 more)

### Community 29 - "CutiMassalService.php"
Cohesion: 0.09
Nodes (10): Hari libur nasional tidak auto-populate on fresh install, HRD/Manager mengajukan cuti sendiri skip level di bawah wewenangnya, Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal, Services, Reset Semua Data Karyawan — khusus admin, hapus permanen, StatusCutiMassal, CutiMassalSudahDibatalkanException, SaldoCutiTidakCukupException (+2 more)

### Community 30 - "JadwalShift"
Cohesion: 0.11
Nodes (4): JadwalShift, Shift, JadwalShiftFactory, JadwalShiftManagementTest

### Community 31 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (10): DatabaseSeeder, {closure#1}(), DepartemenSeeder, {closure#1}(), JabatanSeeder, {closure#1}(), JenisCutiSeeder, KaryawanSeeder (+2 more)

### Community 32 - "app-sidebar.tsx"
Cohesion: 0.12
Nodes (6): AppSidebar(), buildNavGroups(), NavGroup, SidebarContent(), SidebarFooter(), SidebarHeader()

### Community 33 - "dropdown-menu.tsx"
Cohesion: 0.15
Nodes (20): @radix-ui/react-dropdown-menu, AppSidebarHeader(), NotificationBell(), PageProps, OPTIONS, ThemeToggle(), DropdownMenu(), DropdownMenuCheckboxItem() (+12 more)

### Community 34 - "Approval"
Cohesion: 0.14
Nodes (3): Approval, ApprovalPolicy, ApprovalFlowTest

### Community 35 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.12
Nodes (13): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+5 more)

### Community 38 - "app-sidebar-layout.tsx"
Cohesion: 0.17
Nodes (12): AppContent(), Props, AppShell(), Props, SidebarInset(), SidebarProvider(), AppHeaderLayout(), AppSidebarLayout() (+4 more)

### Community 39 - "JadwalShiftController.php"
Cohesion: 0.10
Nodes (4): JadwalShiftController, {closure#1}(), StoreJadwalShiftRequest, UpdateLemburRequest

### Community 40 - "RiwayatSaldoCuti"
Cohesion: 0.15
Nodes (6): RiwayatSaldoCuti, {closure#1}(), {closure#2}(), {closure#3}(), PeriodeCutiService, {closure#1}()

### Community 41 - "user-menu-content.tsx"
Cohesion: 0.17
Nodes (14): @radix-ui/react-avatar, Avatar(), AvatarFallback(), AvatarImage(), UserInfo(), Props, getInitial(), GetInitialsFn (+6 more)

### Community 42 - "components.json"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 43 - "SaldoCutiService"
Cohesion: 0.14
Nodes (3): Periode ke-1 cuti tipe periode = masa kerja minimal, kuota harus 0, Services Services, SaldoCutiService

### Community 45 - "calendar.tsx"
Cohesion: 0.15
Nodes (11): JadwalShiftCalendar(), NAMA_BULAN, PageProps, parseTanggalLocal(), semuaTanggalDiBulan(), SHIFT_BADGE_CLASS, tanggalHariIni(), weekdayFormatter (+3 more)

### Community 46 - "compilerOptions"
Cohesion: 0.12
Nodes (16): compilerOptions, allowJs, baseUrl, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module (+8 more)

### Community 47 - "Master/SaldoCutiController.php"
Cohesion: 0.14
Nodes (3): SaldoCutiController, {closure#1}(), SaldoCutiRequest

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

### Community 58 - "breadcrumbs.tsx"
Cohesion: 0.40
Nodes (9): @radix-ui/react-slot, Breadcrumbs(), Breadcrumb(), BreadcrumbEllipsis(), BreadcrumbItem(), BreadcrumbLink(), BreadcrumbList(), BreadcrumbPage() (+1 more)

### Community 59 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+2 more)

### Community 60 - "Cuti Kerja Pabrik"
Cohesion: 0.20
Nodes (9): Akun Demo, Alur Uji Coba End-to-End, Cuti Kerja Pabrik, Fungsi Utama, Menjalankan dengan Docker, Menjalankan Secara Lokal, Menjalankan Test, Role & Hak Akses (+1 more)

### Community 61 - "toggle-group.tsx"
Cohesion: 0.29
Nodes (8): class-variance-authority, @radix-ui/react-toggle, @radix-ui/react-toggle-group, ToggleGroup(), ToggleGroupContext, ToggleGroupItem(), Toggle(), toggleVariants

### Community 62 - "konfirmasi-kontrak/index.tsx"
Cohesion: 0.24
Nodes (5): KonfirmasiRow(), PageProps, STATUS_LABEL, tambahSatuTahun(), tanggalSetelah()

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

## Knowledge Gaps
- **325 isolated node(s):** `php`, `$schema`, `style`, `rsc`, `tsx` (+320 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 821 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **62 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Karyawan` connect `Karyawan` to `PengajuanCuti`, `TestCase`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `User`, `Illuminate\Http\Request`, `Laporan/SaldoCutiController.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\RedirectResponse`, `InteractsWithKaryawan`, `KaryawanImport.php`, `KompensasiCuti`, `Illuminate\Database\Eloquent\Factories\Factory`, `Departemen`, `CutiMassalService.php`, `JadwalShift`, `Illuminate\Database\Seeder`, `SaldoCutiTest.php`, `JadwalShiftController.php`, `RiwayatSaldoCuti`, `SaldoCutiService`, `Master/SaldoCutiController.php`, `KonfirmasiKontrakCuti`, `PengajuanCutiWhatsAppTest.php`, `.buatDataKaryawanLengkap`?**
  _High betweenness centrality (0.043) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `Approval`, `PengajuanCuti`, `TestCase`, `UserFactory.php`, `.buatDataKaryawanLengkap`, `RiwayatSaldoCuti`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Http\Request`, `Illuminate\Foundation\Http\FormRequest`, `Laporan/SaldoCutiController.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\RedirectResponse`, `InteractsWithKaryawan`, `KaryawanImport.php`, `Departemen`, `Illuminate\Database\Seeder`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Why does `JenisCuti` connect `Karyawan` to `PengajuanCuti`, `TestCase`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Http\Request`, `Laporan/SaldoCutiController.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Inertia\Response`, `Illuminate\Http\RedirectResponse`, `InteractsWithKaryawan`, `KaryawanImport.php`, `KompensasiCuti`, `Illuminate\Database\Eloquent\Factories\Factory`, `Departemen`, `CutiMassalService.php`, `Illuminate\Database\Seeder`, `Approval`, `SaldoCutiTest.php`, `SaldoCutiService`, `Master/SaldoCutiController.php`, `KonfirmasiKontrakCuti`, `JenisCutiController`, `PengajuanCutiWhatsAppTest.php`, `.buatDataKaryawanLengkap`?**
  _High betweenness centrality (0.029) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `style` to the rest of the system?**
  _325 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Karyawan` be split into smaller, more focused modules?**
  _Cohesion score 0.04563233376792699 - nodes in this community are weakly interconnected._
- **Should `Button` be split into smaller, more focused modules?**
  _Cohesion score 0.06846899794299148 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Database\Eloquent\Builder` be split into smaller, more focused modules?**
  _Cohesion score 0.05297334244702666 - nodes in this community are weakly interconnected._