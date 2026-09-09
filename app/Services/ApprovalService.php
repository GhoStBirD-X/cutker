<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Models\Approval;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\User;
use App\Notifications\PengajuanCutiDiajukan;
use App\Notifications\PengajuanCutiDisetujui;
use App\Notifications\PengajuanCutiDitolak;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public const LEVEL_KEPALA_BAGIAN = 1;

    public const LEVEL_HRD = 2;

    /**
     * Level approval final (Manager) yang memotong saldo cuti secara atomik.
     */
    public const LEVEL_MANAGER = 3;

    /**
     * Menyetujui approval pada level saat ini.
     *
     * Level 1 (Kepala Bagian) & Level 2 (HRD): meneruskan pengajuan ke level
     * berikutnya. Level 3 (Manager, final): memotong saldo cuti & mengubah
     * status pengajuan dalam satu DB transaction agar saldo dan status
     * selalu konsisten.
     */
    public function approve(Approval $approval, Karyawan $approver, ?string $catatan = null): Approval
    {
        return DB::transaction(function () use ($approval, $catatan) {
            $approval->update([
                'status' => StatusApproval::Disetujui,
                'tanggal_approval' => now(),
                'catatan' => $catatan,
            ]);

            $pengajuan = $approval->pengajuanCuti()->lockForUpdate()->first();

            match ($approval->level) {
                self::LEVEL_KEPALA_BAGIAN => $this->teruskan($pengajuan, self::LEVEL_HRD, $this->cariHrd()),
                self::LEVEL_HRD => $this->teruskan($pengajuan, self::LEVEL_MANAGER, $this->cariManager()),
                self::LEVEL_MANAGER => $this->setujuiFinal($pengajuan),
                default => null,
            };

            return $approval;
        });
    }

    public function reject(Approval $approval, Karyawan $approver, ?string $catatan = null): Approval
    {
        return DB::transaction(function () use ($approval, $catatan) {
            $approval->update([
                'status' => StatusApproval::Ditolak,
                'tanggal_approval' => now(),
                'catatan' => $catatan,
            ]);

            $pengajuan = $approval->pengajuanCuti;
            $pengajuan->update(['status' => StatusPengajuan::Ditolak]);

            $pengajuan->karyawan->user?->notify(new PengajuanCutiDitolak($pengajuan, $catatan));

            return $approval;
        });
    }

    /**
     * Membuat Approval level berikutnya dan memberi notifikasi ke approver-nya.
     */
    protected function teruskan(PengajuanCuti $pengajuan, int $levelBerikutnya, ?Karyawan $approver): void
    {
        Approval::query()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $approver?->id,
            'level' => $levelBerikutnya,
            'status' => StatusApproval::Pending,
        ]);

        $approver?->user?->notify(new PengajuanCutiDiajukan($pengajuan));
    }

    /**
     * Approval final (Manager): potong saldo cuti & tandai pengajuan disetujui.
     */
    protected function setujuiFinal(PengajuanCuti $pengajuan): void
    {
        $saldo = SaldoCuti::query()
            ->where('karyawan_id', $pengajuan->karyawan_id)
            ->where('jenis_cuti_id', $pengajuan->jenis_cuti_id)
            ->aktif()
            ->lockForUpdate()
            ->latest('id')
            ->firstOrFail();

        $saldo->update([
            'terpakai' => $saldo->terpakai + $pengajuan->jumlah_hari,
            'sisa' => $saldo->kuota === null ? null : $saldo->sisa - $pengajuan->jumlah_hari,
        ]);

        $pengajuan->update(['status' => StatusPengajuan::Disetujui]);

        $pengajuan->karyawan->user?->notify(new PengajuanCutiDisetujui($pengajuan));
    }

    /**
     * HRD dengan approval pending paling sedikit dipilih agar beban kerja merata.
     */
    protected function cariHrd(): ?Karyawan
    {
        return $this->cariApproverPalingLuang('hrd');
    }

    /**
     * Manager (company-wide, approver final) dengan approval pending paling
     * sedikit dipilih agar beban kerja merata, sama seperti pemilihan HRD.
     */
    protected function cariManager(): ?Karyawan
    {
        return $this->cariApproverPalingLuang('manager');
    }

    protected function cariApproverPalingLuang(string $role): ?Karyawan
    {
        $karyawanIds = User::role($role)->pluck('karyawan_id');

        return Karyawan::query()
            ->whereIn('id', $karyawanIds)
            ->withCount(['approvals as approval_pending_count' => fn ($query) => $query->where('status', StatusApproval::Pending)])
            ->orderBy('approval_pending_count')
            ->first();
    }
}
