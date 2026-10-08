<?php

namespace App\Http\Requests\Cuti;

use App\Models\Karyawan;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * HRD/Admin mencatat cuti atas nama karyawan lain. Aturan cutinya sama
 * dengan pengajuan mandiri, kecuali tanggal_mulai tidak dibatasi — boleh
 * backdate jauh (cuti yang sudah terjadi tapi belum tercatat) dan tidak
 * terikat batas waktu pengajuan jenis cuti.
 */
class StoreInputCutiKaryawanRequest extends StorePengajuanCutiRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['hrd', 'admin']) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $karyawan = Karyawan::query()->find($this->integer('karyawan_id'));

        return [
            'karyawan_id' => ['required', 'integer', 'exists:karyawans,id'],
            ...($karyawan ? $this->aturanCuti($karyawan, []) : []),
        ];
    }
}
