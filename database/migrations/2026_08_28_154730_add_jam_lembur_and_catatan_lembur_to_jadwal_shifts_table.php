<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_shifts', function (Blueprint $table) {
            $table->decimal('jam_lembur', 4, 1)->nullable()->after('shift_id');
            $table->string('catatan_lembur')->nullable()->after('jam_lembur');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_shifts', function (Blueprint $table) {
            $table->dropColumn(['jam_lembur', 'catatan_lembur']);
        });
    }
};
