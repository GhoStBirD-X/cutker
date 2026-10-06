<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Karyawan kontrak tidak lagi mendapat Cuti Besar. Hapus baris saldo
     * Cuti Besar milik karyawan kontrak yang belum pernah dipakai, beserta
     * konfirmasi kontrak yang menempel padanya. Baris yang sudah terpakai
     * dibiarkan sebagai jejak historis.
     */
    public function up(): void
    {
        $saldoIds = DB::table('saldo_cutis')
            ->join('karyawans', 'karyawans.id', '=', 'saldo_cutis.karyawan_id')
            ->join('jenis_cutis', 'jenis_cutis.id', '=', 'saldo_cutis.jenis_cuti_id')
            ->where('karyawans.tipe_karyawan', 'kontrak')
            ->where('jenis_cutis.nama_jenis', 'Cuti Besar')
            ->where('saldo_cutis.terpakai', 0)
            ->pluck('saldo_cutis.id');

        DB::table('konfirmasi_kontrak_cutis')->whereIn('saldo_cuti_id', $saldoIds)->delete();
        DB::table('saldo_cutis')->whereIn('id', $saldoIds)->delete();
    }

    /**
     * Penghapusan data tidak bisa dikembalikan.
     */
    public function down(): void
    {
        //
    }
};
