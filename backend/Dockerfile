# ============================================================
# 🏗️ Stage 1 — Build and install dependencies
# ============================================================
FROM composer:2 AS builder

WORKDIR /var/www/html

# Copy only composer files first for caching
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader --prefer-dist

# Copy the rest of the Laravel app
COPY . .

# Generate optimized Laravel cache and config
RUN php artisan config:clear || true \
    && php artisan cache:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true \
    && php artisan optimize || true

# Fix permissions for cache and storage
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache


# ============================================================
# 🚀 Stage 2 — Production runtime (Apache + PHP)
# ============================================================
FROM php:8.2-apache

# Install required PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    zip \
    curl \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy built app from builder stage
COPY --from=builder /var/www/html /var/www/html

# Set proper Apache document root for Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update Apache configs to use Laravel's public directory
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Fix permissions (again, just in case)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose Apache HTTP port
EXPOSE 80

# Run Apache in the foreground
CMD ["apache2-foreground"]
