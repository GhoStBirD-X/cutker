<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->cascadeOnDelete();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cutis')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun')->index();
            $table->unsignedInteger('kuota');
            $table->unsignedInteger('terpakai')->default(0);
            $table->unsignedInteger('sisa');
            $table->timestamps();

            $table->unique(['karyawan_id', 'jenis_cuti_id', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_cutis');
    }
};
