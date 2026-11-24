<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_tanah', function (Blueprint $table) {
            $table->id();
            $table->string('no_persil', 50)->unique();
            $table->string('nik_pemilik', 16)->index();
            $table->decimal('luas_m2', 12, 2);
            $table->decimal('luas_geometri_m2', 12, 2)->nullable();
            $table->string('status_hak', 30)->index(); // SHM/HGB/SKT/Girik/Letter C/Belum bersertifikat
            $table->string('no_sertifikat', 100)->nullable();
            $table->string('penggunaan_lahan', 30)->index(); // pemukiman/sawah/kebun/ladang/lainnya
            $table->string('batas_utara')->nullable();
            $table->string('batas_selatan')->nullable();
            $table->string('batas_timur')->nullable();
            $table->string('batas_barat')->nullable();
            $table->foreignId('wilayah_id')->nullable()->constrained('wilayah')->restrictOnDelete();
            $table->json('geojson')->nullable(); // geometri Polygon (GeoJSON geometry)
            // Bounding box dihitung server saat simpan -> query peta cepat tanpa membaca JSON.
            $table->decimal('min_lng', 10, 7)->nullable();
            $table->decimal('min_lat', 10, 7)->nullable();
            $table->decimal('max_lng', 10, 7)->nullable();
            $table->decimal('max_lat', 10, 7)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['min_lat', 'max_lat', 'min_lng', 'max_lng'], 'bidang_tanah_bbox_index');

            $table->foreign('nik_pemilik')->references('nik')->on('penduduk')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidang_tanah');
    }
};
