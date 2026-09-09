<?php

use App\Enums\StatusKaryawan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('no_hp')->nullable();
            $table->foreignId('departemen_id')->constrained('departemens')->restrictOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatans')->restrictOnDelete();
            $table->date('tanggal_masuk');
            $table->string('status')->default(StatusKaryawan::Aktif->value)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
