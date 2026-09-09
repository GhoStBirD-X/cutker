<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alasan_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_cuti_id')->constrained('jenis_cutis')->restrictOnDelete();
            $table->string('nama_alasan');
            $table->unsignedInteger('jumlah_hari')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['jenis_cuti_id', 'nama_alasan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alasan_cutis');
    }
};
