# Stage 1: Build frontend assets (Node 18 slim)
FROM node:18-slim AS node-builder

WORKDIR /app

# Copy package files ก่อน (เพื่อ cache npm install)
COPY package*.json ./

RUN npm install --legacy-peer-deps

COPY . .

RUN npm run build

# Stage 2: PHP backend
FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev libonig-dev curl \
    && docker-php-ext-install pdo pdo_mysql zip bcmath opcache intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "upload_max_filesize=100M\npost_max_size=100M" > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY --from=node-builder /app/public/build ./public/build

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
