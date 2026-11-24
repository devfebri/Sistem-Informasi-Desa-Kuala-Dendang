<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penduduk', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('no_kk', 16)->index();
            $table->string('nama', 150)->index();
            $table->string('jenis_kelamin', 1); // L / P
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->string('agama', 30);
            $table->string('pekerjaan', 100)->nullable();
            $table->string('status_perkawinan', 30);
            $table->string('hubungan_keluarga', 30);
            $table->string('alamat')->nullable();
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayah')->restrictOnDelete();
            $table->string('status', 20)->default('hidup')->index(); // hidup/meninggal/pindah
            $table->timestamps();

            $table->foreign('no_kk')->references('no_kk')->on('kartu_keluarga')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penduduk');
    }
};
