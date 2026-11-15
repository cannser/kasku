<?php

/**
 * SEMENTARA: halaman diagnosis untuk mencari penyebab error 500 di Vercel.
 * Tidak menampilkan nilai rahasia (hanya "terisi/kosong"), dan password disamarkan di pesan error.
 * HAPUS file ini (dan barisnya di vercel.json) setelah masalah selesai.
 */

use Illuminate\Http\Request;

header('Content-Type: text/plain; charset=utf-8');

$secrets = array_filter([getenv('DB_PASSWORD'), getenv('APP_KEY')]);
$mask = fn (string $s): string => $secrets ? str_replace($secrets, '***', $s) : $s;
$ok = fn (bool $v): string => $v ? 'ya' : 'TIDAK';

echo "== Lingkungan ==\n";
echo 'PHP: ' . PHP_VERSION . "\n";
foreach (['pdo_mysql', 'mbstring', 'openssl', 'ctype', 'fileinfo', 'tokenizer', 'xml', 'json'] as $e) {
    echo "ekstensi $e: " . $ok(extension_loaded($e)) . "\n";
}
echo 'vendor/autoload.php ada: ' . $ok(is_file(__DIR__ . '/../vendor/autoload.php')) . "\n";
echo 'certs/ca-bundle.pem ada: ' . $ok(is_file(__DIR__ . '/../certs/ca-bundle.pem')) . "\n";
echo '/tmp bisa ditulis: ' . $ok(is_writable('/tmp')) . "\n";

echo "\n== Environment Variables (terisi / KOSONG) ==\n";
foreach (['APP_KEY', 'APP_ENV', 'APP_URL', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $k) {
    $v = getenv($k);
    echo "$k: " . (($v !== false && $v !== '') ? 'terisi' : 'KOSONG') . "\n";
}
echo 'APP_KEY berawalan base64: ' . $ok(strncmp((string) getenv('APP_KEY'), 'base64:', 7) === 0) . "\n";

echo "\n== Koneksi database ==\n";
try {
    $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8];
    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/../certs/ca-bundle.pem';
    }
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s', getenv('DB_HOST'), getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE')),
        (string) getenv('DB_USERNAME'),
        (string) getenv('DB_PASSWORD'),
        $options
    );
    echo "Koneksi: BERHASIL\n";
    foreach (['users', 'sessions', 'cache'] as $t) {
        try {
            echo "tabel $t: " . $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn() . " baris\n";
        } catch (Throwable $e) {
            echo "tabel $t: GAGAL - " . $mask($e->getMessage()) . "\n";
        }
    }
} catch (Throwable $e) {
    echo 'Koneksi: GAGAL - ' . get_class($e) . ': ' . $mask($e->getMessage()) . "\n";
}

echo "\n== Menjalankan Laravel untuk /login ==\n";
if (!is_file(__DIR__ . '/../vendor/autoload.php')) {
    exit("vendor tidak ada, dilewati.\n");
}

$tmp = '/tmp/kasku';
foreach (['storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    if (!is_dir("$tmp/$dir")) {
        mkdir("$tmp/$dir", 0775, true);
    }
}
$defaults = [
    'VIEW_COMPILED_PATH'    => "$tmp/storage/framework/views",
    'APP_CONFIG_CACHE'      => "$tmp/bootstrap/cache/config.php",
    'APP_ROUTES_CACHE'      => "$tmp/bootstrap/cache/routes.php",
    'APP_EVENTS_CACHE'      => "$tmp/bootstrap/cache/events.php",
    'APP_PACKAGES_CACHE'    => "$tmp/bootstrap/cache/packages.php",
    'APP_SERVICES_CACHE'    => "$tmp/bootstrap/cache/services.php",
    'LOG_CHANNEL'           => 'stderr',
    'TRUSTED_PROXIES'       => '*',
    'SESSION_SECURE_COOKIE' => 'true',
    'MYSQL_ATTR_SSL_CA'     => dirname(__DIR__) . '/certs/ca-bundle.pem',
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';
    $app->useStoragePath("$tmp/storage");

    $caught = null;
    $app->make(Illuminate\Contracts\Debug\ExceptionHandler::class)
        ->reportable(function (Throwable $e) use (&$caught) {
            $caught = $e;
        });

    $kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(Request::create('/login', 'GET', [], [], [], ['HTTPS' => 'on', 'HTTP_HOST' => 'kasku-six.vercel.app']));
    echo 'Status respons: ' . $response->getStatusCode() . "\n";

    if ($caught) {
        echo 'ERROR: ' . get_class($caught) . "\n";
        echo $mask($caught->getMessage()) . "\n";
        echo 'di ' . basename($caught->getFile()) . ':' . $caught->getLine() . "\n";
        foreach (array_slice($caught->getTrace(), 0, 5) as $i => $f) {
            echo "  #$i " . ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '') . ' (' . basename($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ")\n";
        }
    } else {
        echo "Tidak ada exception tertangkap.\n";
    }
} catch (Throwable $e) {
    echo 'GAGAL saat boot: ' . get_class($e) . ': ' . $mask($e->getMessage()) . "\n";
    echo 'di ' . basename($e->getFile()) . ':' . $e->getLine() . "\n";
}
