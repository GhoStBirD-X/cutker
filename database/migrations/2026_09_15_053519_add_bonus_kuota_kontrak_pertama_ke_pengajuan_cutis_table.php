<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->unsignedInteger('bonus_kuota_kontrak_pertama')->default(0)->after('jumlah_hari_kalender');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropColumn('bonus_kuota_kontrak_pertama');
        });
    }
};
