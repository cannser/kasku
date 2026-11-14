<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menghapus label " (dummy)" dari keterangan transaksi contoh yang dibuat seeder versi lama.
 * Hanya mengubah teks keterangan yang berakhiran " (dummy)"; data lain tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('transactions')
            ->where('description', 'like', '% (dummy)')
            ->update(['description' => DB::raw("REPLACE(description, ' (dummy)', '')")]);
    }

    public function down(): void
    {
        // Tidak ada yang perlu dikembalikan: label hanya hiasan.
    }
};
