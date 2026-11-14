# ==============================================================================
# KasKu (Laravel 12) - image untuk Render (Docker) dan hosting container lain
# Apache + PHP 8.3. Tidak ada secret di sini: semua rahasia dari environment variable.
# ==============================================================================
FROM php:8.3-apache

# Paket sistem + ekstensi PHP yang dibutuhkan Laravel dan koneksi MySQL/TiDB
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip ca-certificates \
    && docker-php-ext-install pdo_mysql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Konfigurasi PHP dan Apache (sembunyikan versi, document root = public/)
COPY docker/php.ini /usr/local/etc/php/conf.d/kasku.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/ports.conf /etc/apache2/ports.conf
COPY docker/security.conf /etc/apache2/conf-available/security-kasku.conf
RUN a2enconf security-kasku

WORKDIR /var/www/html
COPY . .

# Dependency produksi saja (tanpa paket dev/test)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render memberi nomor port lewat env PORT (bawaan 10000)
ENV PORT=10000
EXPOSE 10000

ENTRYPOINT ["entrypoint.sh"]
