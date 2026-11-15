<?php

/**
 * Titik masuk KasKu di Vercel (PHP serverless).
 *
 * Filesystem Vercel hanya-baca, kecuali folder /tmp. Karena itu semua folder yang
 * ditulis Laravel (cache, view terkompilasi, log) dipindah ke /tmp.
 * Tidak ada secret di sini: semua kunci/password dibaca dari Environment Variables Vercel.
 */

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$tmp = '/tmp/kasku';
foreach (['storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    if (!is_dir("$tmp/$dir")) {
        mkdir("$tmp/$dir", 0775, true);
    }
}

// Nilai bawaan (hanya dipakai bila belum diisi di dashboard Vercel)
$defaults = [
    'VIEW_COMPILED_PATH'  => "$tmp/storage/framework/views",
    'APP_CONFIG_CACHE'    => "$tmp/bootstrap/cache/config.php",
    'APP_ROUTES_CACHE'    => "$tmp/bootstrap/cache/routes.php",
    'APP_EVENTS_CACHE'    => "$tmp/bootstrap/cache/events.php",
    'APP_PACKAGES_CACHE'  => "$tmp/bootstrap/cache/packages.php",
    'APP_SERVICES_CACHE'  => "$tmp/bootstrap/cache/services.php",
    'LOG_CHANNEL'         => 'stderr',       // log tampil di Vercel > Logs
    'TRUSTED_PROXIES'     => '*',            // Vercel adalah reverse proxy: baca HTTPS dan IP klien dari header-nya
    'SESSION_SECURE_COOKIE' => 'true',
    // Sertifikat CA publik yang ikut di repo, agar koneksi TLS ke TiDB Cloud pasti terverifikasi
    'MYSQL_ATTR_SSL_CA'   => dirname(__DIR__) . '/certs/ca-bundle.pem',
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath("$tmp/storage");

$app->handleRequest(Request::capture());
