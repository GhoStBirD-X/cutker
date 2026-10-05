<?php

namespace App\Http\Requests\Master;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaldoCutiUpdateMassalRequest extends FormRequest
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
        return [
            'perubahan' => ['required', 'array', 'min:1', 'max:1000'],
            'perubahan.*.id' => ['required', 'integer', 'distinct', 'exists:saldo_cutis,id'],
            'perubahan.*.kuota' => ['present', 'nullable', 'integer', 'min:0', 'max:365'],
            'perubahan.*.terpakai' => ['required', 'integer', 'min:0'],
            'perubahan.*.sisa' => ['present', 'nullable', 'integer', 'min:0'],
            'catatan' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'perubahan.*.kuota' => 'kuota',
            'perubahan.*.terpakai' => 'terpakai',
            'perubahan.*.sisa' => 'sisa',
        ];
    }
}
