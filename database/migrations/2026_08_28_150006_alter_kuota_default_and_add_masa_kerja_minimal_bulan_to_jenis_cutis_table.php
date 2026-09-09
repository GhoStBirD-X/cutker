<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_cutis', function (Blueprint $table) {
            $table->unsignedInteger('kuota_default')->nullable()->change();
            $table->unsignedSmallInteger('masa_kerja_minimal_bulan')->nullable()->after('kuota_default');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_cutis', function (Blueprint $table) {
            $table->dropColumn('masa_kerja_minimal_bulan');
            $table->unsignedInteger('kuota_default')->nullable(false)->change();
        });
    }
};
