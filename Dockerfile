FROM php:8.2-cli

# Install system + PHP dependencies (GD + mbstring + Postgres)
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
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        mbstring \
        zip \
        gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Install PHP dependencies (no scripts during build)
RUN composer install --no-dev --optimize-autoloader --no-scripts

EXPOSE 10000

CMD php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=10000
