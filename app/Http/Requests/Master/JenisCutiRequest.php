<?php

namespace App\Http\Requests\Master;

use App\Enums\JenisKelamin;
use App\Models\JenisCuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JenisCutiRequest extends FormRequest
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
        /** @var JenisCuti|null $jenisCuti */
        $jenisCuti = $this->route('jenis_cuti');

        return [
            'nama_jenis' => ['required', 'string', 'max:255', Rule::unique('jenis_cutis', 'nama_jenis')->ignore($jenisCuti)],
            'kuota_default' => ['nullable', 'integer', 'min:0', 'max:365'],
            'masa_kerja_minimal_bulan' => ['nullable', 'integer', 'min:1', 'max:600'],
            'khusus_gender' => ['nullable', Rule::enum(JenisKelamin::class)],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
