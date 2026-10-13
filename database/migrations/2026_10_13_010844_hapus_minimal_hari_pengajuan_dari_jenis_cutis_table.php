<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batas minimal hari pengajuan per jenis cuti tidak dipakai lagi:
     * pengajuan biasa cukup mulai H+1, cuti mendadak H-1 s.d. H.
     */
    public function up(): void
    {
        Schema::table('jenis_cutis', function (Blueprint $table) {
            $table->dropColumn('minimal_hari_pengajuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_cutis', function (Blueprint $table) {
            $table->unsignedSmallInteger('minimal_hari_pengajuan')->nullable()->after('masa_kerja_minimal_bulan');
        });
    }
};
