<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public $timestamps = false;

    // user_id diisi lewat relasi ($user->categories()->create(...)), bukan dari input
    protected $fillable = ['name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public static function defaultNames(): array
    {
        return ['Gaji', 'Makanan & Minuman', 'Transportasi', 'Belanja', 'Tagihan', 'Hiburan', 'Lainnya'];
    }

    public static function createDefaultsFor(User $user): void
    {
        foreach (self::defaultNames() as $name) {
            $user->categories()->firstOrCreate(['name' => $name]);
        }
    }
}
