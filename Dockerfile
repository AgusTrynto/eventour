# ==========================
# Base Image
# ==========================
FROM php:8.2-apache

# ==========================
# Install system packages
# ==========================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    python3 \
    python3-venv \
    nodejs \
    npm \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        intl

# ==========================
# Enable Apache Rewrite
# ==========================
RUN a2enmod rewrite

# ==========================
# Install Composer
# ==========================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ==========================
# Laravel Public Folder
# ==========================
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# ==========================
# Working Directory
# ==========================
WORKDIR /var/www/html

# ==========================
# Copy Project
# ==========================
COPY . .

# ==========================
# Install Python Dependencies for NCBF .h5 Prediction
# ==========================
ENV NCBF_PYTHON=/opt/ncbf-venv/bin/python

RUN python3 -m venv /opt/ncbf-venv \
    && /opt/ncbf-venv/bin/pip install --upgrade pip \
    && /opt/ncbf-venv/bin/pip install --no-cache-dir -r tools/requirements-ncbf.txt

# ==========================
# Install PHP Dependencies
# ==========================
RUN composer install --no-dev --optimize-autoloader

# ==========================
# Install Frontend Dependencies
# ==========================
RUN npm install
RUN npm run build

# ==========================
# FIX LARAVEL STORAGE (IMPORTANT)
# ==========================
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# ==========================
# Permission
# ==========================
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ==========================
# Laravel Optimization (SAFE VERSION)
# ==========================
RUN php artisan config:clear || true
RUN php artisan route:clear || true

# ==========================
# Port
# ==========================
EXPOSE 80

# ==========================
# Start Apache
# ==========================
CMD ["apache2-foreground"]
