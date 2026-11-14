<?php

/*
 * Pengaturan khusus aplikasi KasKu.
 * Semua nilai dibaca dari .env (lewat env() di file config ini, BUKAN di kode lain,
 * supaya tetap berfungsi saat `php artisan config:cache` dipakai di hosting).
 */
return [

    // Password akun demo untuk database/seeders/DatabaseSeeder.php (opsional).
    // Kosong = seeder membuat password acak dan menampilkannya sekali di terminal.
    'seed_admin_password' => env('SEED_ADMIN_PASSWORD'),
    'seed_user_password'  => env('SEED_USER_PASSWORD'),

    // Rate limiting login (per IP dan per username) dan registrasi (per IP)
    'login_max_attempts'     => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_seconds'    => (int) env('LOGIN_WINDOW_SECONDS', 900),
    'register_max_attempts'  => (int) env('REGISTER_MAX_ATTEMPTS', 5),
    'register_decay_seconds' => (int) env('REGISTER_WINDOW_SECONDS', 3600),

    'per_page' => 15,

    // Hash dummy untuk menyamakan waktu respons login saat username tidak ada
    // (mencegah penyerang menebak username valid dari perbedaan waktu).
    'dummy_hash' => '$2y$12$m63MFi/wC4LadX3tymfKRuM5GttNUoEMdc1yR40c0AwLx08QdxSQ2',

    // CDN dengan versi dipin + hash SRI (Subresource Integrity)
    'cdn' => [
        'bootstrap_css' => [
            'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            'sri' => 'sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH',
        ],
        'bootstrap_js' => [
            'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
            'sri' => 'sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz',
        ],
        'chartjs' => [
            'url' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js',
            'sri' => 'sha384-dug+JxfBvklEQdJ4AYuBBAIScUz0bVN73xpy273gcAwHjb3qI0fXmuYNaNfdyYJG',
        ],
    ],
];
