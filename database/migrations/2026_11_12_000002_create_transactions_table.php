<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete: kategori yang masih dipakai transaksi tidak bisa dihapus
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['income', 'expense']);
            // DECIMAL (bukan float) agar uang tidak mengalami error pembulatan
            $table->decimal('amount', 15, 2);
            $table->string('description', 255)->nullable();
            $table->date('trx_date');
            $table->timestamps();

            $table->index(['user_id', 'trx_date']);
        });

        // Pengaman kedua di level database: nominal harus positif.
        // (SQLite yang dipakai saat testing tidak mendukung ADD CONSTRAINT, jadi dilewati.)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_trx_amount CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
