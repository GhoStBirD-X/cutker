<?php

namespace App\Models;

use App\Enums\StatusApproval;
use Database\Factories\ApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pengajuan_cuti_id
 * @property int $approver_id
 * @property int $level
 * @property StatusApproval $status
 * @property Carbon|null $tanggal_approval
 * @property string|null $catatan
 */
#[Fillable(['pengajuan_cuti_id', 'approver_id', 'level', 'status', 'tanggal_approval', 'catatan'])]
class Approval extends Model
{
    /** @use HasFactory<ApprovalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_approval' => 'datetime',
            'status' => StatusApproval::class,
        ];
    }

    /**
     * @return BelongsTo<PengajuanCuti, $this>
     */
    public function pengajuanCuti(): BelongsTo
    {
        return $this->belongsTo(PengajuanCuti::class);
    }

    /**
     * @return BelongsTo<Karyawan, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'approver_id');
    }
}
