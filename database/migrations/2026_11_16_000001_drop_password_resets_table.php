<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Fitur "lupa password" dihapus dari aplikasi, jadi tabelnya ikut dibuang.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('password_resets');
    }

    public function down(): void
    {
        // Tidak dibuat ulang: fitur sudah dihapus.
    }
};
