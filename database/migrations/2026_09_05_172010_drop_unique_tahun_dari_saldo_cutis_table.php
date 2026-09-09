<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Constraint lama unique(karyawan_id, jenis_cuti_id, tahun) mengasumsikan
     * hanya ada satu baris saldo per tahun kalender per karyawan+jenis cuti.
     * Ini tidak berlaku lagi untuk jenis cuti bertipe periode: dua periode
     * (mis. periode 1 & 2) bisa sah berada di tahun kalender yang sama.
     * Keunikan baris periode sudah dijaga oleh unique(karyawan_id,
     * jenis_cuti_id, periode_ke); baris kalender dijaga oleh pengecekan
     * eksplisit di SaldoCutiService sebelum insert.
     */
    public function up(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->dropUnique(['karyawan_id', 'jenis_cuti_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->unique(['karyawan_id', 'jenis_cuti_id', 'tahun']);
        });
    }
};
