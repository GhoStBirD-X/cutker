<?php

namespace App\Http\Requests\Master;

use App\Enums\AksiMassalSaldoCuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dipakai bersama oleh endpoint pratinjau (GET, tanpa catatan) dan endpoint
 * terapkan (POST, catatan wajib) supaya target & aturan aksinya identik.
 * Target bisa berupa daftar `ids` yang dicentang, atau `semua` = seluruh
 * baris yang cocok dengan filter halaman (lintas halaman pagination).
 */
class SaldoCutiAksiMassalRequest extends FormRequest
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
        $aksi = AksiMassalSaldoCuti::tryFrom((string) $this->input('aksi'));

        return [
            'aksi' => ['required', Rule::enum(AksiMassalSaldoCuti::class)],
            'nilai' => [Rule::requiredIf(fn () => $aksi?->butuhNilai() ?? false), 'nullable', 'integer', 'min:0', 'max:365'],
            'semua' => ['boolean'],
            'ids' => [Rule::requiredIf(fn () => ! $this->boolean('semua')), 'array', 'max:1000'],
            'ids.*' => ['integer'],
            'search' => ['nullable', 'string'],
            'jenis_cuti_id' => ['nullable', 'integer'],
            'departemen_id' => ['nullable', 'integer'],
            'tahun' => ['nullable', 'integer'],
            'catatan' => [Rule::requiredIf(fn () => $this->isMethod('post')), 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal satu baris saldo.',
        ];
    }
}
