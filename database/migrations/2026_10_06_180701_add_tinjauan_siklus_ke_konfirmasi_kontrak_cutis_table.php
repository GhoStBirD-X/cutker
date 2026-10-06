<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konfirmasi_kontrak_cutis', function (Blueprint $table) {
            $table->boolean('tinjauan_siklus')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('konfirmasi_kontrak_cutis', function (Blueprint $table) {
            $table->dropColumn('tinjauan_siklus');
        });
    }
};
