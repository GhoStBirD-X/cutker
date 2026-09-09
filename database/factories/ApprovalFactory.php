<?php

namespace Database\Factories;

use App\Enums\StatusApproval;
use App\Models\Approval;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approval>
 */
class ApprovalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pengajuan_cuti_id' => PengajuanCuti::factory(),
            'approver_id' => Karyawan::factory(),
            'level' => 1,
            'status' => StatusApproval::Pending,
            'tanggal_approval' => null,
            'catatan' => null,
        ];
    }
}
