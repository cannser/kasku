<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Permintaan "lupa password" dari halaman login.
 * - token_hash: hanya hash SHA-256 dari token yang disimpan (token asli hanya ada di email),
 *   jadi kebocoran database tidak membocorkan tautan reset yang masih berlaku.
 * - status 'pending' = belum selesai, 'done' = sudah dipakai atau sudah ditangani admin.
 * - expires_at = batas berlaku tautan email (60 menit). Permintaan tetap terlihat admin sampai ditangani.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('status', 10)->default('pending');
            $table->string('requested_ip', 45)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
