<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peralihan_hak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bidang_tanah_id')->constrained('bidang_tanah')->restrictOnDelete();
            $table->string('nik_pemilik_lama', 16)->index();
            $table->string('nik_pemilik_baru', 16)->index();
            $table->string('jenis', 20)->index(); // jual_beli / waris / hibah / lainnya
            $table->date('tanggal')->index();
            $table->string('no_akta', 100)->nullable();
            $table->string('dokumen')->nullable(); // path di disk privat
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('nik_pemilik_lama')->references('nik')->on('penduduk')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('nik_pemilik_baru')->references('nik')->on('penduduk')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peralihan_hak');
    }
};
