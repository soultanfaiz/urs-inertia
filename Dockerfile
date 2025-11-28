# --- STAGE 1: Build Frontend Assets (Node.js) ---
FROM node:20 as node_build
WORKDIR /app
# Salin file package untuk install dependensi
COPY package*.json ./
# Install dependensi frontend
RUN npm install
# Salin seluruh kode (termasuk resources/js)
COPY . .
# Build aset Vite (akan menghasilkan folder public/build)
RUN npm run build

# --- STAGE 2: Build Backend Dependencies (Composer) ---
FROM composer:2 as composer_build
WORKDIR /app
COPY composer.json composer.lock ./
# Install dependensi PHP tanpa dev-dependencies untuk production
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist

# --- STAGE 3: Final Production Image (PHP + Apache) ---
FROM php:8.2-apache

# Install library sistem yang dibutuhkan Laravel & Postgres
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql bcmath intl zip opcache

# Aktifkan mod_rewrite Apache (Wajib untuk Laravel)
RUN a2enmod rewrite

# Ubah DocumentRoot Apache ke folder /public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Set direktori kerja
WORKDIR /var/www/html

# Salin kode aplikasi dari lokal ke container
COPY . .

# Salin folder vendor dari STAGE 2
COPY --from=composer_build /app/vendor /var/www/html/vendor

# Salin folder public/build (Aset Vite) dari STAGE 1 (PENTING!)
# Ini mengatasi masalah "Vite manifest not found"
COPY --from=node_build /app/public/build /var/www/html/public/build

# Atur hak akses folder storage dan cache agar bisa ditulis oleh www-data
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Gunakan port 8080 (Standar Cloud Run)
EXPOSE 8080
# Konfigurasi port Apache agar mendengarkan port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Perintah yang dijalankan saat container start
# Kita jalankan migrasi database, cache config, lalu start Apache
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    apache2-foreground
