<?php

namespace App\Services;

use App\Models\CutiMassal;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResetDataService
{
    /**
     * Hapus SEMUA karyawan beserta akun login (User) yang terhubung ke
     * mereka, dan semua data turunannya (pengajuan cuti, saldo cuti,
     * riwayat saldo, jadwal shift, kompensasi cuti, konfirmasi kontrak,
     * approval, notifikasi). Master data (Departemen, Jabatan, Jenis Cuti,
     * Alasan Cuti, Shift, Hari Libur) TIDAK disentuh — tetap dipakai untuk
     * data karyawan yang baru.
     *
     * $kecuali (biasanya karyawan milik admin yang menjalankan reset ini,
     * kalau ada) sengaja tidak ikut dihapus supaya yang menjalankan reset
     * tidak kehilangan akses login-nya sendiri di tengah proses.
     *
     * @return array{karyawan: int, user: int, cuti_massal: int}
     */
    public function resetSemuaKaryawan(?Karyawan $kecuali): array
    {
        return DB::transaction(function () use ($kecuali) {
            $karyawanIds = Karyawan::query()
                ->when($kecuali, fn ($query) => $query->where('id', '!=', $kecuali->id))
                ->pluck('id');

            $userIds = User::query()->whereIn('karyawan_id', $karyawanIds)->pluck('id');

            // cuti_massals.dibuat_oleh_id pakai restrictOnDelete ke
            // karyawans, jadi harus dihapus duluan sebelum karyawan-nya.
            $jumlahCutiMassal = CutiMassal::query()->count();
            CutiMassal::query()->delete();

            // Tabel yang tidak auto-cascade dari User (notifications &
            // pivot Spatie tidak punya foreign key ke users, passkeys
            // sudah cascade otomatis lewat migration-nya).
            DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $userIds)->delete();
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $userIds)->delete();
            DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $userIds)->delete();

            User::query()->whereIn('id', $userIds)->delete();

            // Karyawan dihapus terakhir — cascade otomatis membereskan
            // pengajuan_cutis, saldo_cutis, riwayat_saldo_cutis,
            // jadwal_shifts, kompensasi_cutis, konfirmasi_kontrak_cutis,
            // dan approvals (sebagai approver).
            Karyawan::query()->whereIn('id', $karyawanIds)->delete();

            return [
                'karyawan' => $karyawanIds->count(),
                'user' => $userIds->count(),
                'cuti_massal' => $jumlahCutiMassal,
            ];
        });
    }
}
