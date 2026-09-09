<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_cutis', function (Blueprint $table) {
            $table->string('khusus_gender')->nullable()->after('masa_kerja_minimal_bulan');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_cutis', function (Blueprint $table) {
            $table->dropColumn('khusus_gender');
        });
    }
};
