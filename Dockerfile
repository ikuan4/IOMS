FROM php:8.2-cli

# Install system + PHP deps + Node
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    pkg-config \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    nodejs \
    npm \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        zip \
        gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# FRONTEND BUILD (THIS FIXES THE UI)
RUN npm install && npm run build

# Clear caches
RUN php artisan view:clear && php artisan config:clear

EXPOSE 10000
CMD php artisan serve --host=0.0.0.0 --port=10000
