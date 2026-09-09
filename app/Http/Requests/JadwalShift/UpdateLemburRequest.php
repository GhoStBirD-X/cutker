<?php

namespace App\Http\Requests\JadwalShift;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLemburRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('jadwal-shift.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jam_lembur' => ['nullable', 'numeric', 'min:0', 'max:12'],
            'catatan_lembur' => ['nullable', 'string', 'max:255'],
        ];
    }
}
