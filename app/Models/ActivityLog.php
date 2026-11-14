<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'ip', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas. JANGAN memasukkan password atau data sensitif ke teks $action.
     * Kegagalan mencatat log tidak boleh membuat aplikasi error.
     */
    public static function record(string $action, ?int $userId = null): void
    {
        try {
            $uid = $userId ?? auth()->id();

            static::create([
                'user_id'    => $uid === null ? null : (int) $uid,
                'action'     => mb_substr($action, 0, 255),
                'ip'         => request()->ip() ?? '0.0.0.0',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
