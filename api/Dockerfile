FROM php:8.4-fpm-alpine

LABEL org.opencontainers.image.source="https://github.com/sardine-mehico/jobapp"
LABEL org.opencontainers.image.description="JobApp Laravel API"
LABEL org.opencontainers.image.licenses="MIT"

RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libxml2-dev \
    oniguruma-dev \
    bash

RUN docker-php-ext-install pdo pdo_pgsql bcmath dom mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html \
    && mkdir -p /run/nginx

COPY .docker/nginx.conf /etc/nginx/http.d/default.conf
COPY .docker/supervisord.conf /etc/supervisord.conf
COPY .docker/entrypoint.sh /usr/local/bin/jobapp-entrypoint

RUN chmod +x /usr/local/bin/jobapp-entrypoint

EXPOSE 80

CMD ["/usr/local/bin/jobapp-entrypoint"]
