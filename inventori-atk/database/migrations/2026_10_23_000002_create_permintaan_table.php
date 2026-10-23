<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 16)->unique();
            $table->string('kode_barang', 7)->index();
            $table->unsignedInteger('jumlah');
            $table->string('pemohon', 120);
            $table->string('bagian', 80);
            $table->string('status', 20)->default('diajukan');
            $table->string('disetujui_oleh', 120)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan');
    }
};
