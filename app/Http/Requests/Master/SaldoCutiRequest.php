<?php

namespace App\Http\Requests\Master;

use App\Models\JenisCuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaldoCutiRequest extends FormRequest
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
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $jenisCutiId = $isUpdate ? $this->route('saldo_cuti')?->jenis_cuti_id : $this->integer('jenis_cuti_id');
        $bertipePeriode = fn () => JenisCuti::query()->find($jenisCutiId)?->masa_kerja_minimal_bulan !== null;

        return [
            'karyawan_id' => $isUpdate ? ['sometimes'] : ['required', 'integer', 'exists:karyawans,id'],
            'jenis_cuti_id' => $isUpdate ? ['sometimes'] : ['required', 'integer', 'exists:jenis_cutis,id'],
            'tahun' => [Rule::requiredIf(fn () => ! $isUpdate && ! $bertipePeriode()), 'nullable', 'integer', 'min:2000', 'max:2100'],
            'periode_ke' => [Rule::requiredIf(fn () => ! $isUpdate && $bertipePeriode()), 'nullable', 'integer', 'min:1'],
            'periode_mulai' => [Rule::requiredIf(fn () => ! $isUpdate && $bertipePeriode()), 'nullable', 'date'],
            'periode_selesai' => [Rule::requiredIf(fn () => ! $isUpdate && $bertipePeriode()), 'nullable', 'date', 'after:periode_mulai'],
            'kuota' => ['nullable', 'integer', 'min:0', 'max:365'],
            'terpakai' => ['required', 'integer', 'min:0'],
            'sisa' => ['nullable', 'integer', 'min:0'],
            'catatan' => [$isUpdate ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }
}
