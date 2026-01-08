# --- STAGE 1: Build Backend Dependencies (Composer) ---
# Kita jalankan Composer DULUAN agar folder 'vendor' terbentuk
FROM composer:2 as composer_build
WORKDIR /app
COPY composer.json composer.lock ./
# Install dependensi PHP
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist

# --- STAGE 2: Build Frontend Assets (Node.js) ---
# Sekarang baru kita jalankan Node.js
FROM node:20 as node_build
WORKDIR /app
COPY package*.json ./
RUN npm install

# Salin seluruh kode
COPY . .

# [PENTING] Salin folder vendor dari STAGE 1 ke sini
# Agar Vite bisa menemukan "../../vendor/tightenco/ziggy"
COPY --from=composer_build /app/vendor /app/vendor

# Build aset Vite
RUN npm run build

# --- STAGE 3: Final Production Image (PHP + Apache) ---
FROM php:8.2-apache

# Install library sistem
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql bcmath intl zip opcache gd

# Aktifkan mod_rewrite
RUN a2enmod rewrite

# Ubah DocumentRoot Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Set direktori kerja
WORKDIR /var/www/html

# Salin kode aplikasi
COPY . .

# Salin folder vendor dari STAGE 1 (Backend dependencies)
COPY --from=composer_build /app/vendor /var/www/html/vendor

# Salin folder public/build dari STAGE 2 (Frontend assets)
COPY --from=node_build /app/public/build /var/www/html/public/build

# Atur hak akses
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose Port 8080
EXPOSE 8080
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Command Start
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    apache2-foreground
