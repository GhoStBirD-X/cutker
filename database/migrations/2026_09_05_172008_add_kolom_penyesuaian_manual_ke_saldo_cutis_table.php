<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('ditutup_pada');
            $table->foreignId('diubah_oleh_id')->nullable()->after('catatan')->constrained('karyawans')->nullOnDelete();
            $table->dateTime('diubah_pada')->nullable()->after('diubah_oleh_id');
        });
    }

    public function down(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diubah_oleh_id');
            $table->dropColumn(['catatan', 'diubah_pada']);
        });
    }
};
