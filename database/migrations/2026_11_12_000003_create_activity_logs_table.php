<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Catatan: tabel login_attempts dari versi native TIDAK dibuat di sini, karena rate limiting
 * memakai RateLimiter bawaan Laravel (disimpan di cache store).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // nullOnDelete: log tetap ada walau akun dihapus (jejak audit)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 255);
            $table->string('ip', 45);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
