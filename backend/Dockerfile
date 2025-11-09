# ================================
# Stage 1: Build dependencies
# ================================
FROM php:8.2-apache AS builder

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy *entire* Laravel project (to ensure artisan exists)
COPY . .

# Install dependencies (disable scripts to avoid artisan error)
RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist --no-scripts

# Then manually run Laravel optimization commands after vendor exists
RUN php artisan config:clear || true \
    && php artisan cache:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true \
    && php artisan optimize || true

# Fix permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ================================
# Stage 2: Production image
# ================================
FROM php:8.2-apache

WORKDIR /var/www/html

# Copy built app from builder
COPY --from=builder /var/www/html /var/www/html

# Enable mod_rewrite (Laravel routes)
RUN a2enmod rewrite

# Correct Apache permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s \
    CMD curl -f http://localhost || exit 1

CMD ["apache2-foreground"]
