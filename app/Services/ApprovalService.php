<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusPengajuan;
use App\Exceptions\ApprovalSudahDiprosesException;
use App\Models\Approval;
use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\User;
use App\Notifications\PengajuanCutiDiajukan;
use App\Notifications\PengajuanCutiDisetujui;
use App\Notifications\PengajuanCutiDiteruskan;
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
     * Label level approval untuk pesan notifikasi — samakan dengan
     * APPROVAL_LEVEL_LABELS di resources/js/lib/format.ts.
     */
    public static function namaLevel(int $level): string
    {
        return match ($level) {
            self::LEVEL_KEPALA_BAGIAN => 'Kepala Bagian',
            self::LEVEL_HRD => 'HRD',
            self::LEVEL_MANAGER => 'Manager',
            default => "Level {$level}",
        };
    }

    /**
     * Mulai alur approval untuk pengajuan baru, disesuaikan dengan wewenang
     * approval yang sudah dimiliki si pengaju sendiri — supaya orang yang
     * justru berwenang approve di level tertentu tidak perlu menunggu
     * approval dari level di bawah wewenangnya sendiri:
     *
     * - Manager mengajukan cuti untuk dirinya sendiri: tidak ada level di
     *   atas Manager dalam alur ini, jadi langsung disetujui otomatis
     *   (saldo langsung terpotong, tanpa approval manusia).
     * - HRD mengajukan cuti untuk dirinya sendiri: Kepala Bagian & HRD
     *   dilewati (dia sendiri sudah setara/di atas keduanya), langsung ke
     *   Manager sebagai satu-satunya level.
     * - Selain itu (termasuk Kepala Bagian & karyawan biasa): alur normal
     *   3 level, mulai dari Kepala Bagian — kecuali departemennya belum
     *   punya Kepala Bagian sama sekali, di situ level 1 dilewati dan
     *   langsung mulai dari HRD (level Kepala Bagian bukan kolam bersama —
     *   diikat ke satu orang spesifik per departemen — jadi tidak ada yang
     *   bisa "menjemput" approval itu nanti kalau dibiarkan tanpa approver;
     *   beda dengan HRD/Manager yang levelnya kolam bersama per role, lihat
     *   teruskan()).
     */
    public function mulaiAlur(PengajuanCuti $pengajuan, Karyawan $pengaju, ?Karyawan $kepalaBagian): void
    {
        $user = $pengaju->user;

        if ($user?->hasRole('manager')) {
            $this->setujuiFinal($pengajuan);

            return;
        }

        if ($user?->hasRole('hrd')) {
            $this->teruskan($pengajuan, self::LEVEL_MANAGER, 'manager');

            return;
        }

        if (! $kepalaBagian) {
            $this->teruskan($pengajuan, self::LEVEL_HRD, 'hrd');

            return;
        }

        Approval::query()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $kepalaBagian->id,
            'level' => self::LEVEL_KEPALA_BAGIAN,
            'status' => StatusApproval::Pending,
        ]);

        if ($kepalaBagian->user) {
            $kepalaBagian->user->notify(new PengajuanCutiDiajukan($pengajuan));
        }
    }

    /**
     * Menyetujui approval pada level saat ini.
     *
     * Level HRD & Manager adalah kolam bersama: siapa pun user dengan role
     * tersebut boleh bertindak, tidak hanya approver yang tercatat di
     * approver_id (itu cuma nilai awal/hint, lihat cariApproverPalingLuang).
     * Approval di-lock dan dicek masih pending di dalam transaction supaya
     * dua approver yang bertindak bersamaan tidak memproses hal yang sama
     * dua kali (mis. memotong saldo dua kali).
     *
     * Level 1 (Kepala Bagian) & Level 2 (HRD): meneruskan pengajuan ke level
     * berikutnya dan mengabari pengaju bahwa level ini sudah menyetujui.
     * Level 3 (Manager, final): memotong saldo cuti & mengubah
     * status pengajuan dalam satu DB transaction agar saldo dan status
     * selalu konsisten.
     */
    public function approve(Approval $approval, Karyawan $approver, ?string $catatan = null): Approval
    {
        return DB::transaction(function () use ($approval, $approver, $catatan) {
            $approval = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->status !== StatusApproval::Pending) {
                throw new ApprovalSudahDiprosesException($approval->status);
            }

            $approval->update([
                'status' => StatusApproval::Disetujui,
                'approver_id' => $approver->id,
                'tanggal_approval' => now(),
                'catatan' => $catatan,
            ]);

            $pengajuan = $approval->pengajuanCuti()->lockForUpdate()->first();

            match ($approval->level) {
                self::LEVEL_KEPALA_BAGIAN => $this->teruskan($pengajuan, self::LEVEL_HRD, 'hrd'),
                self::LEVEL_HRD => $this->teruskan($pengajuan, self::LEVEL_MANAGER, 'manager'),
                self::LEVEL_MANAGER => $this->setujuiFinal($pengajuan),
                default => null,
            };

            if (in_array($approval->level, [self::LEVEL_KEPALA_BAGIAN, self::LEVEL_HRD], true)) {
                $pengajuan->karyawan->user?->notify(
                    new PengajuanCutiDiteruskan($pengajuan, $approver, $approval->level, $catatan)
                );
            }

            return $approval;
        });
    }

    public function reject(Approval $approval, Karyawan $approver, ?string $catatan = null): Approval
    {
        return DB::transaction(function () use ($approval, $approver, $catatan) {
            $approval = Approval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();

            if ($approval->status !== StatusApproval::Pending) {
                throw new ApprovalSudahDiprosesException($approval->status);
            }

            $approval->update([
                'status' => StatusApproval::Ditolak,
                'approver_id' => $approver->id,
                'tanggal_approval' => now(),
                'catatan' => $catatan,
            ]);

            $pengajuan = $approval->pengajuanCuti;
            $pengajuan->update(['status' => StatusPengajuan::Ditolak]);

            $pengajuan->karyawan->user?->notify(new PengajuanCutiDitolak($pengajuan, $catatan, $approval->level));

            return $approval;
        });
    }

    /**
     * Membuat Approval level berikutnya dan memberi notifikasi ke SELURUH
     * user dengan role tersebut (HRD/Manager melihat & bisa memproses
     * approval siapa pun, bukan cuma satu orang yang ditugaskan).
     */
    protected function teruskan(PengajuanCuti $pengajuan, int $levelBerikutnya, string $role): void
    {
        $approverAwal = $this->cariApproverPalingLuang($role);

        Approval::query()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $approverAwal?->id,
            'level' => $levelBerikutnya,
            'status' => StatusApproval::Pending,
        ]);

        User::role($role)->get()->each(
            fn (User $user) => $user->notify(new PengajuanCutiDiajukan($pengajuan))
        );
    }

    /**
     * Cuti yang dicatat HRD/Admin atas nama karyawan langsung disetujui
     * tanpa alur bertingkat. Satu baris approval level HRD dibuat atas nama
     * yang mencatat supaya riwayat approval tetap menunjukkan siapa yang
     * memutuskan.
     */
    public function setujuiLangsung(PengajuanCuti $pengajuan, Karyawan $olehSiapa): void
    {
        Approval::query()->create([
            'pengajuan_cuti_id' => $pengajuan->id,
            'approver_id' => $olehSiapa->id,
            'level' => self::LEVEL_HRD,
            'status' => StatusApproval::Disetujui,
            'tanggal_approval' => now(),
            'catatan' => 'Dicatat langsung oleh HRD atas nama karyawan.',
        ]);

        $this->setujuiFinal($pengajuan);
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
     * Dipakai sebagai approver_id awal saat approval dibuat (nilai default
     * sebelum ada yang bertindak) — bukan pembatas siapa yang boleh
     * memproses, karena level HRD & Manager adalah kolam bersama.
     */
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
