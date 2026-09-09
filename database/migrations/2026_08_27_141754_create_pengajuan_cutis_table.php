<?php

use App\Enums\StatusPengajuan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cutis')->restrictOnDelete();
            $table->date('tanggal_mulai')->index();
            $table->date('tanggal_selesai');
            $table->unsignedInteger('jumlah_hari');
            $table->string('alasan');
            $table->string('status')->default(StatusPengajuan::Pending->value)->index();
            $table->dateTime('tanggal_pengajuan');
            $table->string('lampiran')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cutis');
    }
};
