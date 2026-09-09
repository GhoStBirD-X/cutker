<?php

namespace App\Http\Requests\Master;

use App\Models\Jabatan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JabatanRequest extends FormRequest
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
        /** @var Jabatan|null $jabatan */
        $jabatan = $this->route('jabatan');

        return [
            'nama_jabatan' => ['required', 'string', 'max:255', Rule::unique('jabatans', 'nama_jabatan')->ignore($jabatan)],
        ];
    }
}
