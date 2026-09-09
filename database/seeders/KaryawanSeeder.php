<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\JadwalShift;
use App\Models\Karyawan;
use App\Models\Shift;
use App\Models\User;
use App\Services\SaldoCutiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $produksi = Departemen::where('kode', 'PRD')->firstOrFail();
        $gudang = Departemen::where('kode', 'GDG')->firstOrFail();
        $qc = Departemen::where('kode', 'QC')->firstOrFail();
        $cleaning = Departemen::where('kode', 'CLS')->firstOrFail();

        $jabatanAdmin = Jabatan::where('nama_jabatan', 'Administrator Sistem')->firstOrFail();
        $jabatanHrd = Jabatan::where('nama_jabatan', 'Staff HRD')->firstOrFail();
        $jabatanKepalaBagian = Jabatan::where('nama_jabatan', 'Kepala Bagian')->firstOrFail();
        $jabatanManager = Jabatan::where('nama_jabatan', 'Manager')->firstOrFail();
        $jabatanKoordinatorShift = Jabatan::where('nama_jabatan', 'Koordinator Shift')->firstOrFail();
        $jabatanPerDepartemen = [
            $produksi->id => Jabatan::where('nama_jabatan', 'Operator Produksi')->firstOrFail(),
            $gudang->id => Jabatan::where('nama_jabatan', 'Staff Gudang')->firstOrFail(),
            $qc->id => Jabatan::where('nama_jabatan', 'Staff Quality Control')->firstOrFail(),
            $cleaning->id => Jabatan::where('nama_jabatan', 'Staff Cleaning Service')->firstOrFail(),
        ];

        $karyawans = collect();

        // Admin: full access
        $karyawans->push($this->buatAkun('Admin Pabrik', 'admin@pabrik.test', $produksi->id, $jabatanAdmin->id, 'admin'));

        // HRD: approval level 2 (meneruskan ke Manager), kelola master data & laporan
        $karyawans->push($this->buatAkun('Siti HRD', 'hrd1@pabrik.test', $produksi->id, $jabatanHrd->id, 'hrd'));
        $karyawans->push($this->buatAkun('Budi HRD', 'hrd2@pabrik.test', $gudang->id, $jabatanHrd->id, 'hrd'));

        // Manager: approval level 3 (final), company-wide seperti HRD
        $karyawans->push($this->buatAkun('Manager Pabrik', 'manager@pabrik.test', $produksi->id, $jabatanManager->id, 'manager'));

        // Kepala Bagian: satu per departemen, approval level 1
        $kepalaBagianPerDepartemen = [];
        foreach ([$produksi, $gudang, $qc, $cleaning] as $departemen) {
            $kepalaBagian = $this->buatAkun(
                'Kepala Bagian '.$departemen->nama_departemen,
                'kabag.'.strtolower($departemen->kode).'@pabrik.test',
                $departemen->id,
                $jabatanKepalaBagian->id,
                'kepala_bagian',
            );
            $kepalaBagianPerDepartemen[$departemen->id] = $kepalaBagian;
            $karyawans->push($kepalaBagian);
        }

        // Koordinator shift: satu per departemen, kelola jadwal shift departemennya saja
        foreach ([$produksi, $gudang, $qc, $cleaning] as $departemen) {
            $karyawans->push($this->buatAkun(
                'Koordinator Shift '.$departemen->nama_departemen,
                'koordinator.'.strtolower($departemen->kode).'@pabrik.test',
                $departemen->id,
                $jabatanKoordinatorShift->id,
                'koordinator_shift',
            ));
        }

        // Karyawan biasa: 10 orang tersebar di 3 departemen
        $departemenRotasi = [$produksi, $gudang, $qc, $cleaning];
        for ($i = 1; $i <= 10; $i++) {
            $departemen = $departemenRotasi[($i - 1) % 3];
            $karyawans->push($this->buatAkun(
                "Karyawan {$i}",
                "karyawan{$i}@pabrik.test",
                $departemen->id,
                $jabatanPerDepartemen[$departemen->id]->id,
                'karyawan',
            ));
        }

        $this->seedSaldoCuti($karyawans);
        $this->seedJadwalShift($karyawans);
    }

    protected function buatAkun(string $nama, string $email, int $departemenId, int $jabatanId, string $role): Karyawan
    {
        $karyawan = Karyawan::factory()->create([
            'nama' => $nama,
            'email' => $email,
            'departemen_id' => $departemenId,
            'jabatan_id' => $jabatanId,
        ]);

        $user = User::factory()->create([
            'name' => $nama,
            'email' => $email,
        ]);
        $user->karyawan_id = $karyawan->id;
        $user->save();
        $user->assignRole($role);

        return $karyawan;
    }

    /**
     * @param  Collection<int, Karyawan>  $karyawans
     */
    protected function seedSaldoCuti($karyawans): void
    {
        $saldoCutiService = app(SaldoCutiService::class);

        foreach ($karyawans as $karyawan) {
            $saldoCutiService->bootstrapUntukKaryawanBaru($karyawan);
        }
    }

    /**
     * @param  Collection<int, Karyawan>  $karyawans
     */
    protected function seedJadwalShift($karyawans): void
    {
        $shifts = Shift::all();
        // Dijadwalkan di 2 minggu terakhir (bukan ke depan) agar tidak
        // bentrok dengan tanggal yang dipakai untuk uji coba pengajuan cuti baru.
        $awalMinggu = Carbon::now()->subWeeks(2)->startOfWeek();

        foreach ($karyawans->values() as $index => $karyawan) {
            for ($hari = 0; $hari < 14; $hari++) {
                $shift = $shifts[($index + $hari) % $shifts->count()];

                JadwalShift::query()->firstOrCreate([
                    'karyawan_id' => $karyawan->id,
                    'tanggal' => $awalMinggu->copy()->addDays($hari)->toDateString(),
                ], [
                    'shift_id' => $shift->id,
                ]);
            }
        }
    }
}
