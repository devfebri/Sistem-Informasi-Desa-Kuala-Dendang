<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 20)->index();      // tambah / ubah / hapus / login / logout / login_gagal
            $table->string('model', 50)->nullable();  // nama kelas, mis. BidangTanah
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('keterangan')->nullable(); // label objek (no. persil, nama, dst.)
            $table->json('perubahan')->nullable();    // atribut yang berubah (tanpa data sensitif)
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['model', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
