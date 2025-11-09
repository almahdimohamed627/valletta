# === Stage 1: Builder ===
FROM php:8.2-fpm AS builder

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev zip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Set working directory
WORKDIR /var/www

# Copy composer files and install dependencies first for caching
COPY composer.json composer.lock ./

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the entire application source
COPY . .

# Ensure permissions (important for Laravel)
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Now install production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# === Stage 2: Production Image ===
FROM php:8.2-fpm

WORKDIR /var/www

# Copy app from builder stage
COPY --from=builder /var/www /var/www

# Copy nginx config if needed (if you use a custom one)
# COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Expose port 80 (if using nginx, or 9000 for PHP-FPM)
EXPOSE 9000

# Use non-root user
USER www-data

CMD ["php-fpm"]
