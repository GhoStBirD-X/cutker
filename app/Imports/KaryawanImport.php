<?php

namespace App\Imports;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use App\Enums\TipeKaryawan;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Services\SaldoCutiService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class KaryawanImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $successCount = 0;

    /**
     * @var array<int, array{row: int, errors: array<int, string>}>
     */
    public array $failures = [];

    public function __construct(
        protected SaldoCutiService $saldoCutiService,
    ) {}

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        $departemenIds = Departemen::query()->pluck('id', 'nama_departemen')
            ->mapWithKeys(fn (int $id, string $nama): array => [mb_strtolower(trim($nama)) => $id]);
        $jabatanIds = Jabatan::query()->pluck('id', 'nama_jabatan')
            ->mapWithKeys(fn (int $id, string $nama): array => [mb_strtolower(trim($nama)) => $id]);

        $seenNip = [];
        $seenEmail = [];

        foreach ($rows as $index => $row) {
            $baris = $index + 2;

            $data = [
                'nip' => trim((string) ($row['nip'] ?? '')),
                'nama' => trim((string) ($row['nama'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')),
                'no_hp' => $this->nullableString($row['no_hp'] ?? null),
                'jenis_kelamin' => trim((string) ($row['jenis_kelamin'] ?? '')),
                'departemen_nama' => trim((string) ($row['departemen'] ?? '')),
                'jabatan_nama' => trim((string) ($row['jabatan'] ?? '')),
                'tanggal_masuk' => $this->parseDate($row['tanggal_masuk'] ?? null),
                'status' => trim((string) ($row['status'] ?? '')) ?: StatusKaryawan::Aktif->value,
                'tipe_karyawan' => trim((string) ($row['tipe_karyawan'] ?? '')),
                'tanggal_akhir_kontrak' => $this->parseDate($row['tanggal_akhir_kontrak'] ?? null),
            ];

            $errors = $this->validateRow($data, $seenNip, $seenEmail, $baris, $departemenIds, $jabatanIds);

            if ($errors !== []) {
                $this->failures[] = ['row' => $baris, 'errors' => $errors];

                continue;
            }

            $seenNip[$data['nip']] = $baris;
            $seenEmail[$data['email']] = $baris;

            $departemenId = $departemenIds[mb_strtolower($data['departemen_nama'])];
            $jabatanId = $jabatanIds[mb_strtolower($data['jabatan_nama'])];

            DB::transaction(function () use ($data, $departemenId, $jabatanId): void {
                $karyawan = Karyawan::query()->create([
                    'nip' => $data['nip'],
                    'nama' => $data['nama'],
                    'email' => $data['email'],
                    'no_hp' => $data['no_hp'],
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'departemen_id' => $departemenId,
                    'jabatan_id' => $jabatanId,
                    'tanggal_masuk' => $data['tanggal_masuk'],
                    'status' => $data['status'],
                    'tipe_karyawan' => $data['tipe_karyawan'],
                    'tanggal_akhir_kontrak' => $data['tanggal_akhir_kontrak'],
                ]);

                $this->saldoCutiService->bootstrapUntukKaryawanBaru($karyawan);
            });

            $this->successCount++;
        }
    }

    /**
     * @param  array<string, string|null>  $data
     * @param  array<string, int>  $seenNip
     * @param  array<string, int>  $seenEmail
     * @param  Collection<string, int>  $departemenIds
     * @param  Collection<string, int>  $jabatanIds
     * @return array<int, string>
     */
    private function validateRow(array $data, array $seenNip, array $seenEmail, int $baris, Collection $departemenIds, Collection $jabatanIds): array
    {
        $errors = [];

        if ($data['nip'] === '') {
            $errors[] = 'NIP wajib diisi.';
        } elseif (isset($seenNip[$data['nip']])) {
            $errors[] = "NIP '{$data['nip']}' duplikat dengan baris {$seenNip[$data['nip']]} di file ini.";
        } elseif (Karyawan::query()->where('nip', $data['nip'])->exists()) {
            $errors[] = "NIP '{$data['nip']}' sudah dipakai karyawan lain.";
        }

        if ($data['nama'] === '') {
            $errors[] = 'Nama wajib diisi.';
        }

        if ($data['email'] === '' || ! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email wajib diisi dan harus valid.';
        } elseif (isset($seenEmail[$data['email']])) {
            $errors[] = "Email '{$data['email']}' duplikat dengan baris {$seenEmail[$data['email']]} di file ini.";
        } elseif (Karyawan::query()->where('email', $data['email'])->exists()) {
            $errors[] = "Email '{$data['email']}' sudah dipakai karyawan lain.";
        }

        if (! JenisKelamin::tryFrom($data['jenis_kelamin'])) {
            $errors[] = "Jenis kelamin '{$data['jenis_kelamin']}' tidak valid (gunakan laki_laki atau perempuan).";
        }

        if (! $departemenIds->has(mb_strtolower($data['departemen_nama']))) {
            $errors[] = "Departemen '{$data['departemen_nama']}' tidak ditemukan. Tambahkan dulu di Master Departemen.";
        }

        if (! $jabatanIds->has(mb_strtolower($data['jabatan_nama']))) {
            $errors[] = "Jabatan '{$data['jabatan_nama']}' tidak ditemukan. Tambahkan dulu di Master Jabatan.";
        }

        if (! $data['tanggal_masuk']) {
            $errors[] = 'Tanggal masuk wajib diisi dan harus format tanggal yang valid.';
        }

        if (! StatusKaryawan::tryFrom($data['status'])) {
            $errors[] = "Status '{$data['status']}' tidak valid (gunakan aktif atau nonaktif).";
        }

        if (! TipeKaryawan::tryFrom($data['tipe_karyawan'])) {
            $errors[] = "Tipe karyawan '{$data['tipe_karyawan']}' tidak valid (gunakan tetap atau kontrak).";
        } elseif ($data['tipe_karyawan'] === TipeKaryawan::Kontrak->value) {
            if (! $data['tanggal_akhir_kontrak']) {
                $errors[] = 'Tanggal akhir kontrak wajib diisi untuk karyawan kontrak.';
            } elseif ($data['tanggal_masuk'] && $data['tanggal_akhir_kontrak'] <= $data['tanggal_masuk']) {
                $errors[] = 'Tanggal akhir kontrak harus setelah tanggal masuk.';
            }
        }

        return $errors;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
