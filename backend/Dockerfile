# --------------------------------------------------------
# Stage: Build dependencies (optional, you could combine)
# --------------------------------------------------------
FROM composer:2 AS builder

WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./

# Install production dependencies (no dev)
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader --prefer-dist

# Copy full application afterwards
COPY . .

# Create required directories explicitly
RUN mkdir -p storage/framework/cache/data storage/framework/views storage/framework/sessions bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Run Laravel optimization (optional)
RUN php artisan config:clear || true \
    && php artisan cache:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true \
    && php artisan optimize || true

# --------------------------------------------------------
# Stage: Production image with Apache
# --------------------------------------------------------
FROM php:8.2-apache

# Install needed extensions
RUN apt-get update && apt-get install -y \
    unzip git zip curl libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy built application
COPY --from=builder /var/www/html /var/www/html

# Change Apache document root to Laravel public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
