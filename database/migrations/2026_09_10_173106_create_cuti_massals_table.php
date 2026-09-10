<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuti_massals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cutis')->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari');
            $table->unsignedInteger('jumlah_hari_kalender');
            $table->string('alasan');
            $table->foreignId('dibuat_oleh_id')->constrained('karyawans')->restrictOnDelete();
            $table->unsignedInteger('jumlah_karyawan')->default(0);
            $table->json('dilewati')->nullable();
            $table->string('status')->default('aktif');
            $table->foreignId('dibatalkan_oleh_id')->nullable()->constrained('karyawans')->nullOnDelete();
            $table->dateTime('dibatalkan_pada')->nullable();
            $table->text('catatan_pembatalan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuti_massals');
    }
};
