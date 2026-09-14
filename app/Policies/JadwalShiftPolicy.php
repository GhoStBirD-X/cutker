<?php

namespace App\Policies;

use App\Models\JadwalShift;
use App\Models\Karyawan;
use App\Models\User;

class JadwalShiftPolicy
{
    /**
     * HRD/admin/koordinator shift mengelola jadwal shift seluruh pabrik lintas
     * departemen (koordinator shift adalah satu peran tunggal yang
     * mengoordinasikan seluruh departemen, bukan satu per departemen).
     */
    public function create(User $user, Karyawan $targetKaryawan): bool
    {
        return $user->hasRole(['hrd', 'admin', 'koordinator_shift']);
    }

    public function delete(User $user, JadwalShift $jadwalShift): bool
    {
        return $user->hasRole(['hrd', 'admin', 'koordinator_shift']);
    }

    /**
     * Mengatur jam lembur pada baris jadwal shift yang sudah ada; aturan
     * akses sama seperti delete.
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
        return $user->hasRole(['hrd', 'admin', 'koordinator_shift']);
    }
}
