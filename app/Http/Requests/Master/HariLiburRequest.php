<?php

namespace App\Http\Requests\Master;

use App\Models\HariLibur;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HariLiburRequest extends FormRequest
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
        /** @var HariLibur|null $hariLibur */
        $hariLibur = $this->route('hari_libur');

        return [
            'tanggal' => ['required', 'date', Rule::unique('hari_liburs', 'tanggal')->ignore($hariLibur)],
            'keterangan' => ['required', 'string', 'max:255'],
        ];
    }
}
