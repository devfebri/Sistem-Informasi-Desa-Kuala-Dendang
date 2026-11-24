<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_surat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('template_view'); // mis. surat.skt
            $table->json('persyaratan')->nullable(); // daftar nama dokumen yang wajib diunggah
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pengajuan_surat', function (Blueprint $table) {
            $table->id();
            $table->string('no_registrasi', 30)->nullable()->unique();
            $table->string('nik_pemohon', 16)->index();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat')->restrictOnDelete();
            $table->foreignId('bidang_tanah_id')->constrained('bidang_tanah')->restrictOnDelete();
            $table->text('keperluan');
            $table->string('status', 20)->default('diajukan')->index(); // diajukan/diverifikasi/disetujui/ditolak/selesai
            $table->text('catatan')->nullable(); // alasan penolakan / catatan petugas

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedSmallInteger('tahun')->nullable();
            $table->unsignedInteger('no_urut')->nullable();
            $table->string('nomor_surat', 60)->nullable()->unique();
            $table->string('kode_validasi', 20)->nullable()->unique(); // untuk QR /validasi/{kode}
            $table->timestamps();

            $table->unique(['tahun', 'no_urut']);
            $table->foreign('nik_pemohon')->references('nik')->on('penduduk')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('dokumen_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_surat_id')->constrained('pengajuan_surat')->cascadeOnDelete();
            $table->string('nama_dokumen');
            $table->string('path_file'); // disk privat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengajuan');
        Schema::dropIfExists('pengajuan_surat');
        Schema::dropIfExists('jenis_surat');
    }
};
