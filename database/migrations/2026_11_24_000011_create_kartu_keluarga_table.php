<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kartu_keluarga', function (Blueprint $table) {
            $table->id();
            $table->string('no_kk', 16)->unique();
            // Tanpa FK ke penduduk (relasi melingkar); divalidasi di aplikasi.
            $table->string('nik_kepala_keluarga', 16)->nullable()->index();
            $table->string('alamat');
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayah')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_keluarga');
    }
};
