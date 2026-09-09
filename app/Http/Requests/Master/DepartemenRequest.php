<?php

namespace App\Http\Requests\Master;

use App\Models\Departemen;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartemenRequest extends FormRequest
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
        /** @var Departemen|null $departemen */
        $departemen = $this->route('departemen');

        return [
            'nama_departemen' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:10', Rule::unique('departemens', 'kode')->ignore($departemen)],
        ];
    }
}
