<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->string('message_id', 36)->unique();
            $table->string('jenis', 40);
            $table->string('penerima', 120);
            $table->text('isi');
            $table->timestamp('dikirim_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_notifikasi');
    }
};
