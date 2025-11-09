# ================================
# Stage 1: Build dependencies
# ================================
FROM php:8.2-apache AS builder

# Set working directory
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

# Copy composer and install dependencies
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy only composer files first to leverage Docker caching
COPY composer.json composer.lock ./

# Install production dependencies only (no-dev)
RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist

# Copy application code
COPY . .

# Make sure Laravel folders are writable
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ================================
# Stage 2: Final Production Image
# ================================
FROM php:8.2-apache

WORKDIR /var/www/html

# Copy Apache configuration for Laravel
COPY --from=builder /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy the built app from the builder stage
COPY --from=builder /var/www/html /var/www/html

# Set ownership
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 80

# Health check endpoint (optional)
HEALTHCHECK --interval=30s --timeout=5s \
    CMD curl -f http://localhost/health || exit 1

# Start Apache in foreground
CMD ["apache2-foreground"]
