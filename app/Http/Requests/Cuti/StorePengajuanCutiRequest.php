<?php

namespace App\Http\Requests\Cuti;

use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Rules\BatasWaktuPengajuanCuti;
use App\Rules\CutiBesarKhususKaryawanTetap;
use App\Rules\CutiBesarSetelahCutiTahunanHabis;
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
     * Cuti mendadak boleh dimulai paling cepat kemarin (H-1, mis. sakit
     * mendadak yang baru sempat diajukan keesokan harinya); pengajuan biasa
     * paling cepat hari ini.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $mendadak = $this->boolean('mendadak');

        return [
            ...$this->aturanCuti($this->user()->karyawan, [
                'after_or_equal:'.($mendadak ? 'yesterday' : 'today'),
                new BatasWaktuPengajuanCuti($this->integer('jenis_cuti_id') ?: null, $mendadak),
            ]),
            'mendadak' => ['sometimes', 'boolean'],
            'alasan_mendadak' => ['required_if:mendadak,true', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->boolean('mendadak')
            ? ['tanggal_mulai.after_or_equal' => 'Cuti mendadak paling cepat dimulai kemarin (H-1).']
            : [];
    }

    /**
     * Aturan pengajuan cuti untuk satu karyawan, dipakai bersama oleh
     * pengajuan mandiri dan input cuti oleh HRD/Admin
     * (StoreInputCutiKaryawanRequest) — yang berbeda hanya batas
     * tanggal_mulai-nya.
     *
     * @param  array<int, ValidationRule|string>  $aturanTanggalMulai
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function aturanCuti(Karyawan $karyawan, array $aturanTanggalMulai): array
    {
        $tanggalMulai = (string) $this->input('tanggal_mulai');
        $alasanCutiId = $this->integer('alasan_cuti_id') ?: null;

        return [
            'jenis_cuti_id' => [
                'required',
                'integer',
                'exists:jenis_cutis,id',
                new MasaKerjaMencukupi($karyawan),
                new SesuaiGenderJenisCuti($karyawan),
                new CutiBesarKhususKaryawanTetap($karyawan),
                new CutiBesarSetelahCutiTahunanHabis($karyawan),
            ],
            'alasan_cuti_id' => [
                Rule::requiredIf(fn () => JenisCuti::query()->find($this->integer('jenis_cuti_id'))?->alasanCutis()->exists() ?? false),
                'nullable',
                'integer',
                Rule::exists('alasan_cutis', 'id')->where('jenis_cuti_id', $this->input('jenis_cuti_id')),
            ],
            'tanggal_mulai' => ['required', 'date', ...$aturanTanggalMulai],
            'tanggal_selesai' => [
                'required',
                'date',
                'after_or_equal:tanggal_mulai',
                new TidakOverlapPengajuanCuti($karyawan->id, $tanggalMulai),
                new TidakBentrokJadwalShift($karyawan->id, $tanggalMulai),
                new SesuaiDurasiAlasanCuti($tanggalMulai, $alasanCutiId),
            ],
            'alasan' => ['required', 'string', 'max:255'],
            'lampiran' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }
}
