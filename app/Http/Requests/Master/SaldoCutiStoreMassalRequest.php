<?php

namespace App\Http\Requests\Master;

use App\Models\JenisCuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaldoCutiStoreMassalRequest extends FormRequest
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
        $bertipePeriode = fn () => JenisCuti::query()->find($this->integer('jenis_cuti_id'))?->masa_kerja_minimal_bulan !== null;

        return [
            'karyawan_ids' => ['required', 'array', 'min:1'],
            'karyawan_ids.*' => ['integer', 'distinct', 'exists:karyawans,id'],
            'jenis_cuti_id' => ['required', 'integer', 'exists:jenis_cutis,id'],
            'tahun' => [Rule::requiredIf(fn () => ! $bertipePeriode()), 'nullable', 'integer', 'min:2000', 'max:2100'],
            'kuota' => ['nullable', 'integer', 'min:0', 'max:365'],
            'terpakai' => ['required', 'integer', 'min:0', Rule::when($this->filled('kuota'), ['lte:kuota'])],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'karyawan_ids.required' => 'Pilih minimal satu karyawan.',
            'terpakai.lte' => 'Terpakai tidak boleh melebihi kuota.',
        ];
    }
}
