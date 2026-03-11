# ============================================================
# InvenBill Pro — Production Dockerfile
# Multi-stage build: Composer install + optimized PHP-FPM
# ============================================================

# ── Stage 1: Composer Dependencies ─────────────────────
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ── Stage 2: Production PHP-FPM ────────────────────────
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev icu-dev oniguruma-dev \
    nginx supervisor curl

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql gd mbstring zip intl opcache bcmath

# Install Redis extension
RUN apk add --no-cache autoconf g++ make \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf g++ make

# PHP production config
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/php.ini /usr/local/etc/php/conf.d/99-invenbill.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Application code
WORKDIR /var/www/html
COPY --from=composer /app/vendor ./vendor
COPY . .

# Create required directories
RUN mkdir -p logs cache uploads/products uploads/backups \
    && chown -R www-data:www-data logs cache uploads \
    && chmod -R 775 logs cache uploads

# Health check
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD curl -sf http://localhost/index.php?page=health || exit 1

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
