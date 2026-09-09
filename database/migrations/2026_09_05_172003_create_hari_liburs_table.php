<?php

use App\Enums\SumberHariLibur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hari_liburs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('keterangan');
            $table->string('sumber')->default(SumberHariLibur::Nasional->value);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hari_liburs');
    }
};
