<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan reset password. Semua kolom diisi eksplisit lewat forceFill di controller
 * (tidak ada mass assignment dari input pengguna), jadi $fillable sengaja dikosongkan.
 */
class PasswordReset extends Model
{
    protected $table = 'password_resets';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Token disimpan sebagai hash SHA-256; token asli tidak pernah masuk database. */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Permintaan yang masih perlu ditangani admin (7 hari terakhir). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('created_at', '>=', now()->subDays(7));
    }

    /** Tautan email yang masih bisa dipakai: belum dipakai dan belum kedaluwarsa. */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('status', 'pending')->where('expires_at', '>', now());
    }
}
