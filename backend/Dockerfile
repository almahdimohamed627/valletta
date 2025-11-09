# Stage 1: Build environment and Composer dependencies
FROM php:8.4-fpm AS builder

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    unzip \
    libpq-dev \
    libonig-dev \
    libssl-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    opcache \
    intl \
    zip \
    bcmath \
    soap \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www

COPY . .

# Install Composer and dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --prefer-dist

# Stage 2: Production environment
FROM nginx:stable AS nginx_base

# Copy Nginx configuration
COPY ./nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy PHP and Laravel app from builder stage
COPY --from=builder /var/www /var/www

# Install PHP-FPM
RUN apt-get update && apt-get install -y --no-install-recommends php8.2-fpm php8.2-mysql php8.2-xml php8.2-bcmath php8.2-intl php8.2-zip php8.2-curl php8.2-mbstring && \
    apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Adjust permissions
RUN chown -R www-data:www-data /var/www

# Set workdir
WORKDIR /var/www

# Expose web port
EXPOSE 80

# Start both services (Nginx + PHP-FPM)
CMD service php8.2-fpm start && nginx -g "daemon off;"
