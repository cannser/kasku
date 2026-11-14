#!/bin/sh
# Dijalankan setiap container start. Semua konfigurasi dibaca dari environment variable.
set -e
cd /var/www/html

# Cache konfigurasi dan view (route:cache sengaja tidak dipakai: ada route closure)
php artisan config:cache
php artisan view:cache

# Migrasi: hanya MENAMBAH tabel yang belum ada, tidak menghapus data.
# Dicoba ulang beberapa kali karena database cloud kadang butuh beberapa detik untuk bangun.
tries=0
until php artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge 6 ]; then
        echo "Migrasi gagal setelah $tries percobaan. Periksa DB_* dan MYSQL_ATTR_SSL_CA." >&2
        exit 1
    fi
    echo "Database belum siap, mencoba lagi dalam 5 detik ($tries/6)..." >&2
    sleep 5
done

exec apache2-foreground
