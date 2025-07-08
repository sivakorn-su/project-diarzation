# Stage 1: Build frontend assets (Node 18)
FROM node:18-alpine AS node-builder

WORKDIR /app

# Copy package.json and lockfile
COPY package*.json ./
# If you use pnpm/yarn แก้ตามนั้น

RUN npm install

COPY . .

RUN npm run build

# Stage 2: PHP backend with extensions
FROM php:8.2-fpm-alpine

# ติดตั้ง PHP extensions ที่ Laravel ต้องการ
RUN apk add --no-cache \
    bash \
    git \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev \
    curl \
    nodejs \
    npm \
    autoconf \
    gcc \
    g++ \
    make \
    && docker-php-ext-install pdo pdo_mysql zip bcmath opcache intl

# ตั้งค่า upload limit
RUN echo "upload_max_filesize=100M\npost_max_size=100M" > /usr/local/etc/php/conf.d/uploads.ini

# ติดตั้ง Composer (copy จาก official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy Laravel project files
COPY . .

# Copy built frontend assets จาก stage 1
COPY --from=node-builder /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# รัน npm ci กับ build อีกครั้งถ้าต้องการ (Optional)
# RUN npm ci && npm run build

# ให้สิทธิ์ folder storage และ bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
