# ----------------------------------------------------
# STAGE 1: BUILD ASET FRONEND (Vue/Inertia)
# ----------------------------------------------------
FROM node:20-alpine AS frontend_builder

WORKDIR /app

# Copy package files dan install Node dependencies
COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile

# Copy seluruh kode aplikasi
COPY . .

# Jalankan build assets Inertia/Vue
RUN yarn build

# ----------------------------------------------------
# STAGE 2: BUILD IMAGE PRODUKSI (PHP-FPM untuk Laravel)
# ----------------------------------------------------
FROM php:8.2-fpm-alpine AS production

# Install OS dependencies dan PHP extensions yang diperlukan
# Untuk Neon (PostgreSQL), kita perlu pdo_pgsql
RUN apk add --no-cache \
    postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy semua file source code
COPY . .

# Copy aset yang sudah di-build dari Stage 1
# Sesuaikan path public/build jika Anda menggunakan Vite
COPY --from=frontend_builder /app/public/build public/build

# Instalasi Composer dependencies (hanya untuk produksi)
RUN composer install --no-dev --optimize-autoloader

# Buat folder cache dan log, dan set permission
RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Expose port 9000 (port default FPM)
EXPOSE 9000

# Perintah utama untuk menjalankan PHP-FPM
CMD ["php-fpm"]
