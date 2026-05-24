FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Copy existing application directory
COPY . /var/www/html

# Update Apache DocumentRoot to point to the public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set proper permissions for the storage folder (if it exists)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap || true

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install application dependencies (if composer.json exists)
RUN composer install --no-interaction --optimize-autoloader || true

EXPOSE 80
