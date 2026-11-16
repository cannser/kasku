<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Di production semua URL dibuat dengan https://
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Tampilan pagination memakai Bootstrap 5
        Paginator::useBootstrapFive();

        // Rate limit registrasi: per alamat IP (dipakai di routes/web.php: throttle:register)
        RateLimiter::for('register', function (Request $request) {
            $minutes = max(1, intdiv((int) config('kasku.register_decay_seconds'), 60));

            return Limit::perMinutes($minutes, (int) config('kasku.register_max_attempts'))
                ->by($request->ip());
        });

        // Rate limit "lupa password": 5 permintaan per jam per IP (dipakai di routes/web.php: throttle:forgot)
        RateLimiter::for('forgot', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Di Blade: @rupiah($angka) menghasilkan "Rp 25.000" (sudah di-escape)
        Blade::directive('rupiah', function (string $expression): string {
            return "<?php echo e(\\App\\Support\\Format::rupiah({$expression})); ?>";
        });
    }
}
