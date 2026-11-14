FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip ca-certificates \
    && docker-php-ext-install pdo_mysql opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/kasku.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/ports.conf /etc/apache2/ports.conf
COPY docker/security.conf /etc/apache2/conf-available/security-kasku.conf
RUN a2enconf security-kasku

RUN useradd -m -u 1000 user \
    && mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R user:user /var/run/apache2 /var/lock/apache2 /var/log/apache2
ENV APACHE_RUN_USER=user \
    APACHE_RUN_GROUP=user

WORKDIR /var/www/html
COPY --chown=user:user . .

ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R user:user storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=7860
EXPOSE 7860

USER user
ENTRYPOINT ["entrypoint.sh"]