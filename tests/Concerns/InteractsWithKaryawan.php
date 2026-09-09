<?php

namespace Tests\Concerns;

use App\Models\Karyawan;
use App\Models\User;

trait InteractsWithKaryawan
{
    /**
     * @param  array<string, mixed>  $karyawanAttributes
     */
    protected function karyawanUser(string $role, array $karyawanAttributes = []): User
    {
        $karyawan = Karyawan::factory()->create($karyawanAttributes);

        $user = User::factory()->create();
        $user->karyawan_id = $karyawan->id;
        $user->save();
        $user->assignRole($role);

        return $user->fresh(['karyawan']);
    }

    /**
     * User dengan role tapi belum dihubungkan ke data karyawan sama sekali
     * (mis. baru dibuat admin lewat Kelola User dan belum di-link).
     */
    protected function unlinkedUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }
}
