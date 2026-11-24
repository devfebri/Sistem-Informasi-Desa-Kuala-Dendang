<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('name');
            $table->enum('role', ['admin', 'kades', 'warga'])->default('warga')->index()->after('password');
            $table->string('nik', 16)->nullable()->index()->after('role');
            $table->boolean('is_active')->default(true)->after('nik');
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'nik', 'is_active']);
        });
    }
};
