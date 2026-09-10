<?php

namespace App\Http\Requests\Cuti;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCutiMassalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->karyawan !== null
            && $this->user()->hasPermissionTo('cuti.massal.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis_cuti_id' => ['required', 'integer', 'exists:jenis_cutis,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string', 'max:255'],
            'karyawan_ids' => ['required', 'array', 'min:1'],
            'karyawan_ids.*' => ['integer', 'exists:karyawans,id'],
        ];
    }
}
