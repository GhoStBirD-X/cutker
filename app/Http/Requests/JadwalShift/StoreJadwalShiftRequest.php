<?php

namespace App\Http\Requests\JadwalShift;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreJadwalShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('jadwal-shift.manage');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string|\Closure>
     */
    public function rules(): array
    {
        return [
            'karyawan_ids' => ['required', 'array', 'min:1'],
            'karyawan_ids.*' => ['integer', 'exists:karyawans,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => [
                'required',
                'date',
                'after_or_equal:tanggal_mulai',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $mulai = Carbon::parse($this->input('tanggal_mulai'));
                    $selesai = Carbon::parse($value);

                    if ($mulai->diffInDays($selesai) > 31) {
                        $fail('Rentang tanggal maksimal 31 hari sekaligus.');
                    }
                },
            ],
        ];
    }
}
