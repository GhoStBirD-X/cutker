<?php

use App\Enums\StatusKonfirmasiKontrak;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfirmasi_kontrak_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('saldo_cuti_id')->constrained('saldo_cutis')->cascadeOnDelete();
            $table->unsignedSmallInteger('periode_ke');
            $table->date('tanggal_batas');
            $table->string('status')->default(StatusKonfirmasiKontrak::Menunggu->value);
            $table->foreignId('dikonfirmasi_oleh_id')->nullable()->constrained('karyawans')->nullOnDelete();
            $table->dateTime('dikonfirmasi_pada')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfirmasi_kontrak_cutis');
    }
};
