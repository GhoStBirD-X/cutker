<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_saldo_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cutis')->cascadeOnDelete();
            $table->unsignedSmallInteger('periode_ke');
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->unsignedInteger('kuota');
            $table->unsignedInteger('terpakai');
            $table->unsignedInteger('sisa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_saldo_cutis');
    }
};
