<?php

namespace App\Http\Requests\Approval;

use App\Enums\StatusApproval;
use App\Models\Approval;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalActionRequest extends FormRequest
{
    /**
     * Approval yang sudah diproses approver lain (HRD/Manager adalah kolam
     * bersama, halaman bisa basi) tetap diloloskan untuk user yang berhak
     * melihatnya, supaya ApprovalService melempar ApprovalSudahDiprosesException
     * dan controller menampilkan toast yang jelas — bukan halaman 403.
     */
    public function authorize(): bool
    {
        /** @var Approval $approval */
        $approval = $this->route('approval');

        if ($approval->status !== StatusApproval::Pending) {
            return $this->user()->can('view', $approval);
        }

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
