<?php

namespace App\Policies;

use App\Enums\StatusPengajuan;
use App\Models\PengajuanCuti;
use App\Models\User;

class PengajuanCutiPolicy
{
    /**
     * Karyawan hanya melihat daftar pengajuan miliknya sendiri; Kepala
     * Bagian, HRD, Manager, dan admin melihat pengajuan di ruang lingkup
     * mereka (difilter di query).
     */
    public function viewAny(User $user): bool
    {
        return $user->karyawan !== null || $user->hasAnyRole(['kepala_bagian', 'hrd', 'manager', 'admin']);
    }

    public function view(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->karyawan?->id === $pengajuanCuti->karyawan_id) {
            return true;
        }

        if ($user->hasRole('kepala_bagian')) {
            return $user->karyawan?->departemen_id === $pengajuanCuti->karyawan->departemen_id;
        }

        return $user->hasAnyRole(['hrd', 'manager', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->karyawan !== null && $user->hasPermissionTo('cuti.create');
    }

    /**
     * Karyawan hanya boleh membatalkan pengajuan miliknya sendiri selama
     * masih berstatus pending (belum diproses approval manapun).
     */
    public function cancel(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        return $user->karyawan?->id === $pengajuanCuti->karyawan_id
            && $pengajuanCuti->status === StatusPengajuan::Pending;
    }
}
