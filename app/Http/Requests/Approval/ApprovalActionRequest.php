<?php

namespace App\Http\Requests\Approval;

use App\Models\Approval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Approval $approval */
        $approval = $this->route('approval');

        return $this->user()->can('act', $approval);
    }

    /**
     * Catatan wajib diisi untuk pengajuan mendadak, supaya approver
     * meninggalkan jejak pertimbangan eksplisit alih-alih persetujuan
     * satu klik tanpa catatan untuk kondisi darurat.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Approval $approval */
        $approval = $this->route('approval');

        return [
            'catatan' => [Rule::requiredIf(fn () => $approval->pengajuanCuti->is_mendadak), 'nullable', 'string', 'max:255'],
        ];
    }
}
