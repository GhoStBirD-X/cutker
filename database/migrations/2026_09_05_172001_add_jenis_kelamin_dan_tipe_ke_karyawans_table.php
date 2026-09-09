<?php

use App\Enums\JenisKelamin;
use App\Enums\TipeKaryawan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->string('jenis_kelamin')->default(JenisKelamin::LakiLaki->value)->after('no_hp');
            $table->string('tipe_karyawan')->default(TipeKaryawan::Tetap->value)->after('status');
            $table->date('tanggal_akhir_kontrak')->nullable()->after('tipe_karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn(['jenis_kelamin', 'tipe_karyawan', 'tanggal_akhir_kontrak']);
        });
    }
};
