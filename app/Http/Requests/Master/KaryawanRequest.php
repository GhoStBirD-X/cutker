<?php

namespace App\Http\Requests\Master;

use App\Enums\JenisKelamin;
use App\Enums\StatusKaryawan;
use App\Enums\TipeKaryawan;
use App\Models\Karyawan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('master-data.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Karyawan|null $karyawan */
        $karyawan = $this->route('karyawan');

        return [
            'nip' => ['required', 'string', 'max:50', Rule::unique('karyawans', 'nip')->ignore($karyawan)],
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('karyawans', 'email')->ignore($karyawan)],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['required', Rule::enum(JenisKelamin::class)],
            'departemen_id' => ['required', 'integer', 'exists:departemens,id'],
            'jabatan_id' => ['required', 'integer', 'exists:jabatans,id'],
            'tanggal_masuk' => ['required', 'date'],
            'status' => ['required', Rule::enum(StatusKaryawan::class)],
            'tipe_karyawan' => ['required', Rule::enum(TipeKaryawan::class)],
            'tanggal_akhir_kontrak' => [
                Rule::requiredIf(fn () => $this->input('tipe_karyawan') === TipeKaryawan::Kontrak->value),
                'nullable',
                'date',
                'after:tanggal_masuk',
            ],
        ];
    }
}
