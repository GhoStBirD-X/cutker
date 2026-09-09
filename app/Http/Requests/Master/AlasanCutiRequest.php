<?php

namespace App\Http\Requests\Master;

use App\Models\AlasanCuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlasanCutiRequest extends FormRequest
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
        /** @var AlasanCuti|null $alasanCuti */
        $alasanCuti = $this->route('alasan_cuti');

        return [
            'jenis_cuti_id' => ['required', 'integer', 'exists:jenis_cutis,id'],
            'nama_alasan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('alasan_cutis', 'nama_alasan')
                    ->where('jenis_cuti_id', $this->input('jenis_cuti_id'))
                    ->ignore($alasanCuti),
            ],
            'jumlah_hari' => ['nullable', 'integer', 'min:1', 'max:365'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
