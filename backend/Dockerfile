FROM php:8.2-apache

# 1️⃣ System packages
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev zip git unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd bcmath opcache \
    && a2enmod rewrite

WORKDIR /var/www/html

# 2️⃣ Copy all application files *first*
COPY . .

# 3️⃣ Install Composer (globally)
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

# 4️⃣ Install dependencies after code is copied
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && php artisan key:generate || true \
    && php artisan config:clear || true \
    && php artisan cache:clear || true

# 5️⃣ Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# 6️⃣ Apache configuration
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]
