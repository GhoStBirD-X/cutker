<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->unsignedSmallInteger('periode_ke')->nullable()->after('tahun');
            $table->date('periode_mulai')->nullable()->after('periode_ke');
            $table->date('periode_selesai')->nullable()->after('periode_mulai');
            $table->timestamp('ditutup_pada')->nullable()->after('sisa');

            $table->unique(['karyawan_id', 'jenis_cuti_id', 'periode_ke']);
        });
    }

    public function down(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->dropUnique(['karyawan_id', 'jenis_cuti_id', 'periode_ke']);
            $table->dropColumn(['periode_ke', 'periode_mulai', 'periode_selesai', 'ditutup_pada']);
        });
    }
};
