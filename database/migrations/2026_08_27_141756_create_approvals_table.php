<?php

use App\Enums\StatusApproval;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_cuti_id')->constrained('pengajuan_cutis')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('karyawans')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('status')->default(StatusApproval::Pending->value)->index();
            $table->dateTime('tanggal_approval')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
