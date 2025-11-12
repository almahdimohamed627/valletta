FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies including PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    nodejs \
    npm \
    libpq-dev  # ← Add this for PostgreSQL

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions including PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql mbstring exif pcntl bcmath gd  # ← Add pdo_pgsql

# Enable Apache modules
RUN a2enmod rewrite

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy dependency files first for better caching
COPY composer.json composer.lock package.json package-lock.json* ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader --no-dev

# Install Node.js dependencies
RUN npm install

# Copy application code
COPY . .

# Create Laravel cache directories and set proper permissions
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views

RUN chgrp -R www-data /var/www/html/storage \
    && chgrp -R www-data /var/www/html/bootstrap/cache \
    && chmod -R g+w /var/www/html/storage \
    && chmod -R g+w /var/www/html/bootstrap/cache \
    && chmod -R 775 storage/framework

# Copy Apache virtual host configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Optimize Laravel for production
RUN composer dump-autoload --optimize

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]