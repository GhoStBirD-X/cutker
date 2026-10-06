# Graph Report - cutker  (2026-10-06)

## Corpus Check
- 406 files · ~110,248 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 31 file(s) not represented in the graph (top: (none) 19, .stub 6, .example 1)

## Summary
- 2663 nodes · 7523 edges · 142 communities (66 shown, 76 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 40 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `a9e4017a`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- SaldoCuti
- Button
- sidebar.tsx
- KaryawanFactory.php
- Illuminate\Database\Eloquent\Factories\HasFactory
- Karyawan
- ajukan.tsx
- Card
- react
- SaldoCutiController
- AlasanCuti
- User
- app-sidebar-header.tsx
- Illuminate\Http\RedirectResponse
- DepartemenController.php
- Illuminate\Contracts\Validation\ValidationRule
- Illuminate\Http\Request
- AlasanCutiController.php
- layout.tsx
- package.json
- Illuminate\Foundation\Http\FormRequest
- cn
- SaldoCutiService
- dependencies
- CLAUDE.md
- Laporan/SaldoCutiController.php
- Departemen
- bootstrap/app.php
- PengajuanCuti
- JadwalShift
- Illuminate\Database\Seeder
- app-sidebar.tsx
- dropdown-menu.tsx
- Approval
- Illuminate\Database\Schema\Blueprint
- PeriodeCutiService
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
- CutiMassalTest.php
- PasswordResetTest.php
- scripts
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- Illuminate\Console\Command
- composer.json
- require
- eslint.config.js
- SumberHariLibur
- require-dev
- SaldoSeverity
- ApprovalPolicy
- JenisCutiController.php
- KaryawanImport.php
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
- CutiMassalService.php
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
- StatusKaryawan
- 2026_08_27_141750_create_shifts_table.php
- 2026_08_27_141752_create_karyawans_table.php
- 2026_08_27_141754_create_pengajuan_cutis_table.php
- Cuti Kerja Pabrik
- 2026_08_27_141757_create_jadwal_shifts_table.php
- 2026_08_27_142636_create_notifications_table.php
- PengajuanCutiPolicy
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
- vite.config.ts
- index.ts
- DepartemenFactory
- JabatanFactory
- welcome.tsx
- app-header.tsx
- auth.ts
- TestCase
- SaldoCutiTest.php
- 0001_01_01_000001_create_cache_table.php
- 2026_08_28_150007_alter_kuota_and_sisa_nullable_on_saldo_cutis_table.php
- 2024_01_01_000000_create_passkeys_table.php
- 2026_08_27_141756_create_approvals_table.php
- 2026_09_05_172003_create_hari_liburs_table.php

## God Nodes (most connected - your core abstractions)
1. `Karyawan` - 180 edges
2. `JenisCuti` - 174 edges
3. `cn()` - 166 edges
4. `SaldoCuti` - 145 edges
5. `User` - 110 edges
6. `Button()` - 99 edges
7. `PengajuanCuti` - 82 edges
8. `react` - 73 edges
9. `TestCase` - 73 edges
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
- `Arsitektur Singkat` --references--> `PengajuanCutiDisetujui`  [INFERRED]
  README.md → app/Notifications/PengajuanCutiDisetujui.php

## Import Cycles
- None detected.

## Communities (142 total, 76 thin omitted)

### Community 0 - "SaldoCuti"
Cohesion: 0.07
Nodes (4): SaldoCuti, PengajuanCutiFormTest, PengajuanCutiTest, SaldoCutiMassalTest

### Community 1 - "Button"
Cohesion: 0.08
Nodes (43): @inertiajs/react, @laravel/passkeys, InputError(), MasterNav(), Pagination(), PasskeyRegistration(), Props, PasskeyVerify() (+35 more)

### Community 2 - "sidebar.tsx"
Cohesion: 0.15
Nodes (18): @radix-ui/react-slot, Separator(), SidebarContext, SidebarGroupAction(), SidebarInput(), SidebarMenuAction(), SidebarMenuBadge(), SidebarMenuButton() (+10 more)

### Community 5 - "Karyawan"
Cohesion: 0.05
Nodes (16): CutiMassal, JenisCuti, Karyawan, AlasanCutiFactory, ApprovalFactory, {closure#1}(), CutiMassalFactory, JenisCutiFactory (+8 more)

### Community 6 - "ajukan.tsx"
Cohesion: 0.12
Nodes (28): PaginationProps, PILIHAN_PER_HALAMAN, terjemahkanLabel(), Select(), SelectContent(), SelectItem(), SelectScrollDownButton(), SelectScrollUpButton() (+20 more)

### Community 7 - "Card"
Cohesion: 0.07
Nodes (50): ICONS, SaldoCutiInline(), SaldoCutiInlineProps, SaldoCutiMeter(), SaldoCutiMeterProps, CLASSES, LABELS, StatusBadge() (+42 more)

### Community 8 - "react"
Cohesion: 0.08
Nodes (26): input-otp, react, Heading(), EmptyState(), ManagePasskeys(), Props, ManageTwoFactor(), Props (+18 more)

### Community 9 - "SaldoCutiController"
Cohesion: 0.13
Nodes (4): SaldoCutiController, SaldoCutiAksiMassalRequest, SaldoCutiRequest, SaldoCutiStoreMassalRequest

### Community 10 - "AlasanCuti"
Cohesion: 0.29
Nodes (3): AlasanCuti, AlasanCutiSeeder, {closure#1}()

### Community 11 - "User"
Cohesion: 0.14
Nodes (3): User, AuthenticationTest, SecurityTest

### Community 12 - "app-sidebar-header.tsx"
Cohesion: 0.12
Nodes (23): sonner, AppContent(), Props, AppShell(), Props, AppSidebarHeader(), Breadcrumbs(), Breadcrumb() (+15 more)

### Community 13 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.08
Nodes (6): JadwalShiftController, ShiftController, NotificationController, StoreJadwalShiftRequest, UpdateLemburRequest, ShiftRequest

### Community 15 - "Illuminate\Contracts\Validation\ValidationRule"
Cohesion: 0.06
Nodes (12): {closure#1}(), StorePengajuanCutiRequest, {closure#1}(), {closure#1}(), {closure#1}(), {closure#2}(), BatasWaktuPengajuanCuti, MasaKerjaMencukupi (+4 more)

### Community 16 - "Illuminate\Http\Request"
Cohesion: 0.06
Nodes (17): StatusCutiMassal, StatusKompensasiCuti, HasPerPage, CutiMassalController, KompensasiCutiController, KonfirmasiKontrakController, PengajuanCutiController, LaporanCutiController (+9 more)

### Community 18 - "layout.tsx"
Cohesion: 0.15
Nodes (22): NavFooter(), NavMain(), Collapsible(), CollapsibleContent(), CollapsibleTrigger(), SidebarGroup(), SidebarGroupContent(), SidebarGroupLabel() (+14 more)

### Community 19 - "package.json"
Cohesion: 0.05
Nodes (37): private, $schema, type, babel-plugin-react-compiler, clsx, cobe, concurrently, eslint (+29 more)

### Community 20 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.06
Nodes (11): CreateNewUser, ResetUserPassword, PasswordValidationRules, ProfileValidationRules, TambahKaryawanBaruCutiMassalRequest, {closure#1}(), SaldoCutiImportRequest, SaldoCutiUpdateMassalRequest (+3 more)

### Community 21 - "cn"
Cohesion: 0.14
Nodes (24): class-variance-authority, AlertError(), Alert(), AlertDescription(), AlertTitle(), alertVariants, CardFooter(), NavigationMenu() (+16 more)

### Community 22 - "SaldoCutiService"
Cohesion: 0.08
Nodes (14): Hari libur nasional tidak auto-populate on fresh install, HRD/Manager mengajukan cuti sendiri skip level di bawah wewenangnya, Kontrak pertama (K1) dapat bonus kuota, bukan minus, dari Cuti Massal, Services, Reset Semua Data Karyawan — khusus admin, hapus permanen, Periode ke-1 cuti tipe periode = masa kerja minimal, kuota harus 0, Services Services, CutiMassalService (+6 more)

### Community 23 - "dependencies"
Cohesion: 0.05
Nodes (37): dependencies, class-variance-authority, clsx, cobe, concurrently, globals, @inertiajs/react, @inertiajs/vite (+29 more)

### Community 25 - "CLAUDE.md"
Cohesion: 0.06
Nodes (33): APIs & Eloquent Resources, Application Structure & Architecture, Artisan, Conventions, Deployment, Do Things the Laravel Way, Documentation Files, Foundational Context (+25 more)

### Community 26 - "Laporan/SaldoCutiController.php"
Cohesion: 0.08
Nodes (6): {closure#10}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#6}(), SaldoCutiController

### Community 27 - "Departemen"
Cohesion: 0.15
Nodes (4): Departemen, Jabatan, KaryawanImportTest, KaryawanManagementTest

### Community 28 - "bootstrap/app.php"
Cohesion: 0.10
Nodes (6): EnsureKaryawanLinked, HandleAppearance, HandleInertiaRequests, {closure#1}(), {closure#2}(), {closure#3}()

### Community 29 - "PengajuanCuti"
Cohesion: 0.07
Nodes (15): Notifications, Notifikasi cuti sengaja tidak ShouldQueue, approvals.approver_id nullable — kolam kosong tidak boleh crash, ApprovalSudahDiprosesException, PengajuanCuti, WhatsAppChannel, PengajuanCutiDiajukan, PengajuanCutiDisetujui (+7 more)

### Community 30 - "JadwalShift"
Cohesion: 0.09
Nodes (5): JadwalShift, Shift, JadwalShiftFactory, ShiftFactory, JadwalShiftManagementTest

### Community 31 - "Illuminate\Database\Seeder"
Cohesion: 0.09
Nodes (10): DatabaseSeeder, {closure#1}(), DepartemenSeeder, {closure#1}(), JabatanSeeder, {closure#1}(), JenisCutiSeeder, KaryawanSeeder (+2 more)

### Community 32 - "app-sidebar.tsx"
Cohesion: 0.12
Nodes (6): AppSidebar(), buildNavGroups(), NavGroup, SidebarContent(), SidebarFooter(), SidebarHeader()

### Community 33 - "dropdown-menu.tsx"
Cohesion: 0.11
Nodes (26): @radix-ui/react-dropdown-menu, NavUser(), NotificationBell(), PageProps, OPTIONS, ThemeToggle(), DropdownMenu(), DropdownMenuCheckboxItem() (+18 more)

### Community 34 - "Approval"
Cohesion: 0.14
Nodes (3): Approval, ApprovalFlowTest, StatusPengajuanCutiWhatsAppTest

### Community 35 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.12
Nodes (13): {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}() (+5 more)

### Community 36 - "PeriodeCutiService"
Cohesion: 0.23
Nodes (7): RiwayatSaldoCuti, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), PeriodeCutiService

### Community 38 - "use-appearance.tsx"
Cohesion: 0.08
Nodes (38): withApp(), AppearanceToggleTab(), ThemeColorPicker(), THEMES, Toaster(), TooltipProvider(), Appearance, applyTheme() (+30 more)

### Community 39 - "KaryawanController.php"
Cohesion: 0.08
Nodes (7): KaryawanImportTemplateExport, {closure#3}(), KaryawanController, KaryawanImportRequest, KaryawanRequest, ResetDataKaryawanRequest, ResetDataService

### Community 42 - "components.json"
Cohesion: 0.11
Nodes (17): aliases, components, hooks, lib, ui, utils, iconLibrary, rsc (+9 more)

### Community 43 - "Controller"
Cohesion: 0.08
Nodes (7): ApprovalController, {closure#1}(), Controller, ProfileController, SecurityController, ApprovalActionRequest, TwoFactorAuthenticationRequest

### Community 46 - "compilerOptions"
Cohesion: 0.12
Nodes (16): compilerOptions, allowJs, baseUrl, esModuleInterop, forceConsistentCasingInFileNames, isolatedModules, jsx, module (+8 more)

### Community 47 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.06
Nodes (23): Akun login otomatis dibuat sekalian dengan karyawan, Imports, KaryawanImportDataSheet, KaryawanImportPanduanRoleSheet, PengajuanCutiExport, SaldoCutiExport, SaldoCutiMasterExport, {closure#1}() (+15 more)

### Community 48 - "devDependencies"
Cohesion: 0.13
Nodes (15): devDependencies, babel-plugin-react-compiler, eslint, eslint-config-prettier, eslint-import-resolver-typescript, @eslint/js, eslint-plugin-import, eslint-plugin-react (+7 more)

### Community 49 - "CutiMassalTest.php"
Cohesion: 0.11
Nodes (3): StatusKonfirmasiKontrak, TipeKaryawan, KonfirmasiKontrakTest

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

### Community 59 - "require-dev"
Cohesion: 0.20
Nodes (10): require-dev, larastan/larastan, laravel/boost, laravel/pail, laravel/pao, laravel/pint, laravel/sail, mockery/mockery (+2 more)

### Community 60 - "SaldoSeverity"
Cohesion: 0.14
Nodes (4): SaldoSeverity, HasPerPageTest, resolve(), SaldoSeverityTest

### Community 63 - "KaryawanImport.php"
Cohesion: 0.08
Nodes (9): AksiMassalSaldoCuti, {closure#2}(), {closure#3}(), KaryawanImport, SaldoCutiImport, {closure#3}(), {closure#5}(), {closure#7}() (+1 more)

### Community 65 - "calendar.tsx"
Cohesion: 0.07
Nodes (27): Filters, formatRupiah(), Karyawan(), KompensasiCutiIndex(), PageProps, Periode(), rateValid(), JadwalShiftCalendar() (+19 more)

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

### Community 78 - "CutiMassalService.php"
Cohesion: 0.06
Nodes (6): StatusApproval, StatusPengajuan, CutiMassalSudahDibatalkanException, SaldoCutiTidakCukupException, {closure#1}(), {closure#1}()

### Community 101 - "Cuti Kerja Pabrik"
Cohesion: 0.20
Nodes (9): Akun Demo, Alur Uji Coba End-to-End, Cuti Kerja Pabrik, Fungsi Utama, Menjalankan dengan Docker, Menjalankan Secara Lokal, Menjalankan Test, Role & Hak Akses (+1 more)

### Community 127 - "master/saldo-cuti.tsx"
Cohesion: 0.09
Nodes (54): lucide-react, DeleteUser(), PasskeyItem(), Props, AksiMassalOption, BarisPratinjau, NilaiSaldo, Props (+46 more)

### Community 128 - "vite.config.ts"
Cohesion: 0.29
Nodes (6): @inertiajs/vite, laravel-vite-plugin, @laravel/vite-plugin-wayfinder, @tailwindcss/vite, vite, @vitejs/plugin-react

### Community 129 - "index.ts"
Cohesion: 0.07
Nodes (17): PageProps, LemburSummary, PageProps, cariSaldo(), KaryawanRow, KOLOM_JENIS_CUTI, LaporanSaldoCuti(), PageProps (+9 more)

### Community 132 - "welcome.tsx"
Cohesion: 0.24
Nodes (6): AppLogoFull(), Globe(), MARKERS, warnaTema(), FEATURES, Welcome()

### Community 133 - "app-header.tsx"
Cohesion: 0.10
Nodes (28): AppHeader(), mainNavItems, Props, rightNavItems, AppLogo(), AppLogoIcon(), Avatar(), AvatarFallback() (+20 more)

### Community 134 - "auth.ts"
Cohesion: 0.18
Nodes (11): Auth, JenisKelamin, Role, TipeKaryawan, TwoFactorSecretKey, TwoFactorSetupData, User, InertiaConfig (+3 more)

### Community 135 - "TestCase"
Cohesion: 0.03
Nodes (36): JenisKelamin, RoleSeeder, InteractsWithKaryawan, {closure#1}(), PasswordConfirmationTest, RegistrationTest, {closure#1}(), TwoFactorChallengeTest (+28 more)

### Community 137 - "SaldoCutiTest.php"
Cohesion: 0.06
Nodes (3): DashboardController, KonfirmasiKontrakCuti, SaldoCutiTest

## Knowledge Gaps
- **338 isolated node(s):** `php`, `$schema`, `style`, `rsc`, `tsx` (+333 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 867 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **76 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Karyawan` connect `Karyawan` to `SaldoCuti`, `KaryawanFactory.php`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `TestCase`, `SaldoCutiTest.php`, `User`, `Illuminate\Http\RedirectResponse`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `SaldoCutiService`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Laporan/SaldoCutiController.php`, `Departemen`, `PengajuanCuti`, `JadwalShift`, `Illuminate\Database\Seeder`, `PeriodeCutiService`, `KaryawanController.php`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Eloquent\Builder`, `CutiMassalTest.php`, `KaryawanImport.php`, `UserController.php`, `.buatDataKaryawanLengkap`, `JadwalShiftPolicy`, `CutiMassalService.php`, `StatusKaryawan`?**
  _High betweenness centrality (0.056) - this node is a cross-community bridge._
- **Why does `JenisCuti` connect `Karyawan` to `SaldoCuti`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `TestCase`, `SaldoCutiController`, `AlasanCuti`, `SaldoCutiTest.php`, `Illuminate\Contracts\Validation\ValidationRule`, `Illuminate\Http\Request`, `AlasanCutiController.php`, `SaldoCutiService`, `Illuminate\Database\Eloquent\Relations\HasMany`, `Laporan/SaldoCutiController.php`, `Departemen`, `PengajuanCuti`, `Illuminate\Database\Seeder`, `Approval`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `Illuminate\Database\Eloquent\Builder`, `CutiMassalTest.php`, `JenisCutiController.php`, `KaryawanImport.php`, `.buatDataKaryawanLengkap`, `CutiMassalService.php`, `StatusKaryawan`?**
  _High betweenness centrality (0.054) - this node is a cross-community bridge._
- **Why does `SaldoCuti` connect `SaldoCuti` to `Approval`, `Illuminate\Database\Eloquent\Factories\HasFactory`, `PeriodeCutiService`, `Karyawan`, `TestCase`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `SaldoCutiTest.php`, `SaldoCutiController`, `.buatDataKaryawanLengkap`, `CutiMassalService.php`, `Illuminate\Database\Eloquent\Builder`, `Illuminate\Http\Request`, `Illuminate\Contracts\Validation\ValidationRule`, `CutiMassalTest.php`, `SaldoCutiService`, `Laporan/SaldoCutiController.php`, `PengajuanCuti`, `KaryawanImport.php`?**
  _High betweenness centrality (0.035) - this node is a cross-community bridge._
- **What connects `php`, `$schema`, `style` to the rest of the system?**
  _338 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `SaldoCuti` be split into smaller, more focused modules?**
  _Cohesion score 0.07180851063829788 - nodes in this community are weakly interconnected._
- **Should `Button` be split into smaller, more focused modules?**
  _Cohesion score 0.08139876579488686 - nodes in this community are weakly interconnected._
- **Should `Karyawan` be split into smaller, more focused modules?**
  _Cohesion score 0.04949608062709966 - nodes in this community are weakly interconnected._