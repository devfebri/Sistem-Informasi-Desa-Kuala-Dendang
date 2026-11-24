<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_dusun', 100)->index();
            $table->string('rt', 3);
            $table->string('rw', 3);
            $table->json('geojson')->nullable(); // batas wilayah (Tahap 3)
            $table->timestamps();

            $table->unique(['nama_dusun', 'rt', 'rw']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
