<?php

namespace App\Policies;

use App\Models\JadwalShift;
use App\Models\Karyawan;
use App\Models\User;

class JadwalShiftPolicy
{
    /**
     * HRD/admin mengelola jadwal shift seluruh pabrik; koordinator shift hanya
     * untuk karyawan di departemennya sendiri.
     */
    public function create(User $user, Karyawan $targetKaryawan): bool
    {
        if ($user->hasRole(['hrd', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('jadwal-shift.manage')
            && $user->karyawan?->departemen_id === $targetKaryawan->departemen_id;
    }

    public function delete(User $user, JadwalShift $jadwalShift): bool
    {
        if ($user->hasRole(['hrd', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('jadwal-shift.manage')
            && $user->karyawan?->departemen_id === $jadwalShift->karyawan->departemen_id;
    }

    /**
     * Mengatur jam lembur pada baris jadwal shift yang sudah ada; aturan
     * akses sama seperti delete (HRD/admin bebas, koordinator shift terbatas
     * departemennya sendiri).
     */
    public function update(User $user, JadwalShift $jadwalShift): bool
    {
        return $this->delete($user, $jadwalShift);
    }

    /**
     * Menentukan departemen mana yang boleh dilihat penuh (bukan hanya jadwal milik sendiri).
     */
    public function viewDepartemen(User $user, ?int $departemenId): bool
    {
        if ($user->hasRole(['hrd', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('jadwal-shift.manage')
            && $departemenId !== null
            && $user->karyawan?->departemen_id === $departemenId;
    }
}
