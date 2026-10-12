<?php

use App\Enums\StatusAntrianWhatsapp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antrian_whatsapps', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 20);
            $table->text('pesan');
            $table->string('status')->default(StatusAntrianWhatsapp::Pending->value)->index();
            $table->unsignedInteger('percobaan')->default(0);
            $table->text('error_terakhir')->nullable();
            $table->dateTime('terkirim_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian_whatsapps');
    }
};
