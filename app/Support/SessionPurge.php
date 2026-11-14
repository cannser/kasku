<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Mencabut sesi aktif milik seorang pengguna dari server.
 * Hanya bekerja untuk SESSION_DRIVER=database (sesi tersimpan di tabel "sessions").
 * Dipakai saat admin mereset password atau menonaktifkan akun, dan saat pengguna
 * mengganti passwordnya (sesi di perangkat lain ikut berakhir).
 */
class SessionPurge
{
    public static function forUser(int $userId, ?string $exceptSessionId = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $query = DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $userId);

        if ($exceptSessionId !== null) {
            $query->where('id', '!=', $exceptSessionId);
        }

        $query->delete();
    }
}
