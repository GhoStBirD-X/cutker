<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResetDataKaryawanRequest extends FormRequest
{
    /**
     * Frasa konfirmasi yang harus diketik ulang persis oleh admin sebelum
     * reset dijalankan — pengaman tambahan di luar tombol biasa karena
     * aksi ini menghapus permanen semua karyawan & akun login mereka.
     */
    public const FRASA_KONFIRMASI = 'HAPUS SEMUA KARYAWAN';

    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'konfirmasi' => ['required', Rule::in([self::FRASA_KONFIRMASI])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'konfirmasi.in' => 'Ketik ulang frasa konfirmasi persis seperti yang diminta.',
        ];
    }
}
