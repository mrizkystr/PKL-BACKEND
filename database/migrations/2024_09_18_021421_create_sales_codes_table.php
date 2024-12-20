<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_codes', function (Blueprint $table) {
            $table->id();  // Primary key
            $table->string('mitra_nama')->nullable();
            $table->string('nama')->nullable();
            $table->string('sto')->nullable();
            $table->string('id_mitra')->nullable();
            $table->string('nama_mitra')->nullable();
            $table->string('role')->nullable();
            $table->string('kode_agen')->nullable();
            $table->string('kode_baru')->nullable();
            $table->string('no_telp_valid')->nullable();
            $table->string('nama_sa_2')->nullable();
            $table->string('status_wpi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_codes');
    }
};
