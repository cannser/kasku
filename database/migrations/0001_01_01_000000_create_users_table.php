<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Menggantikan migrasi users bawaan Laravel (nama file sengaja sama agar menimpa).
 * Kolom "password" menyimpan hash bcrypt (konvensi Laravel; setara password_hash di versi native).
 * Tabel "sessions" dipakai driver sesi database, dan kolom user_id-nya membuat sesi
 * milik satu pengguna bisa dicabut dari server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 30)->unique();
            $table->string('email', 120)->unique();
            $table->string('password');
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
