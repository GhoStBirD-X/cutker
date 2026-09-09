<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable()->change();
            $table->unsignedInteger('sisa')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('saldo_cutis', function (Blueprint $table) {
            $table->unsignedInteger('kuota')->nullable(false)->change();
            $table->unsignedInteger('sisa')->nullable(false)->change();
        });
    }
};
