# ─────────────────────────────────────────────
# Stage 1: Build dependencies with Composer
# ─────────────────────────────────────────────
FROM php:8.4-fpm AS builder

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libpq-dev \
    && docker-php-ext-install -j$(nproc) \
       pdo_mysql pdo_pgsql bcmath zip intl opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Copy the rest of the application code
COPY . .

# ─────────────────────────────────────────────
# Stage 2: Runtime image with Nginx + PHP-FPM
# ─────────────────────────────────────────────
FROM nginx:stable AS runtime

# Install PHP-FPM and extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    php8.2-fpm php8.2-cli php8.2-mysql php8.2-pgsql \
    php8.2-bcmath php8.2-intl php8.2-zip php8.2-curl php8.2-mbstring php8.2-xml php8.2-soap \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy Nginx configuration
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy built application from builder stage
COPY --from=builder /var/www /var/www

# Set working directory
WORKDIR /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www

# Expose HTTP port
EXPOSE 80

# Start both PHP-FPM and Nginx
CMD service php8.2-fpm start && nginx -g "daemon off;"
