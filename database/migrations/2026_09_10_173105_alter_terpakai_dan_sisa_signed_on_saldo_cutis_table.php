<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuti massal boleh membuat saldo karyawan baru menjadi minus (bukan
     * diblokir/dikecualikan), sehingga kolom ini harus signed. Kolom `kuota`
     * tidak diubah karena kuota tidak pernah negatif.
     */
    public function up(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->integer('terpakai')->default(0)->change();
            $table->integer('sisa')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->unsignedInteger('terpakai')->default(0)->change();
            $table->unsignedInteger('sisa')->nullable()->change();
        });
    }
};
