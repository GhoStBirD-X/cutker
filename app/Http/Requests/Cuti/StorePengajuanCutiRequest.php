<?php

namespace App\Http\Requests\Cuti;

use App\Models\JenisCuti;
use App\Models\PengajuanCuti;
use App\Rules\MasaKerjaMencukupi;
use App\Rules\SesuaiDurasiAlasanCuti;
use App\Rules\SesuaiGenderJenisCuti;
use App\Rules\TidakBentrokJadwalShift;
use App\Rules\TidakOverlapPengajuanCuti;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePengajuanCutiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->karyawan !== null
            && $this->user()->can('create', PengajuanCuti::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $karyawan = $this->user()->karyawan;
        $karyawanId = $karyawan->id;
        $tanggalMulai = (string) $this->input('tanggal_mulai');
        $alasanCutiId = $this->integer('alasan_cuti_id') ?: null;

        return [
            'jenis_cuti_id' => [
                'required',
                'integer',
                'exists:jenis_cutis,id',
                new MasaKerjaMencukupi($karyawan),
                new SesuaiGenderJenisCuti($karyawan),
            ],
            'alasan_cuti_id' => [
                Rule::requiredIf(fn () => JenisCuti::query()->find($this->integer('jenis_cuti_id'))?->alasanCutis()->exists() ?? false),
                'nullable',
                'integer',
                Rule::exists('alasan_cutis', 'id')->where('jenis_cuti_id', $this->input('jenis_cuti_id')),
            ],
            'tanggal_mulai' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_selesai' => [
                'required',
                'date',
                'after_or_equal:tanggal_mulai',
                new TidakOverlapPengajuanCuti($karyawanId, $tanggalMulai),
                new TidakBentrokJadwalShift($karyawanId, $tanggalMulai),
                new SesuaiDurasiAlasanCuti($tanggalMulai, $alasanCutiId),
            ],
            'alasan' => ['required', 'string', 'max:255'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }
}
