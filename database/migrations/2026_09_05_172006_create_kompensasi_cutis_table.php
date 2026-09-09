<?php

use App\Enums\StatusKompensasiCuti;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kompensasi_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cutis')->cascadeOnDelete();
            $table->foreignId('riwayat_saldo_cuti_id')->nullable()->constrained('riwayat_saldo_cutis')->nullOnDelete();
            $table->unsignedInteger('jumlah_hari');
            $table->decimal('rate_per_hari', 12, 2)->nullable();
            $table->decimal('total_rupiah', 14, 2)->nullable();
            $table->string('status')->default(StatusKompensasiCuti::MenungguDiproses->value);
            $table->foreignId('diproses_oleh_id')->nullable()->constrained('karyawans')->nullOnDelete();
            $table->dateTime('diproses_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kompensasi_cutis');
    }
};
