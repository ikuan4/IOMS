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

# Avoid Laravel 419 on oversized multipart requests (PHP drops POST body when > post_max_size).
# Keep app-level validation enforcing the 2MB limit; this just ensures the request reaches Laravel.
RUN { \
      echo 'upload_max_filesize=16M'; \
      echo 'post_max_size=20M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Ensure migrations run on every container start (Render may override CMD).
RUN chmod +x /var/www/docker/entrypoint.sh \
    && cp /var/www/docker/entrypoint.sh /entrypoint.sh \
    && chmod +x /entrypoint.sh

# PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# FRONTEND BUILD (THIS FIXES THE UI)
RUN npm install && npm run build

# Clear caches
RUN php artisan view:clear && php artisan config:clear

EXPOSE 10000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["sh", "-lc", "exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
