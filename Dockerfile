FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    default-mysql-client \
    libpq-dev \
    postgresql-client \
    ghostscript \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath xml zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Copy custom Apache configuration
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies (if composer.json exists)
RUN if [ -f "composer.json" ]; then \
    rm -rf vendor; \
    git config --global --add safe.directory /var/www/html; \
    export COMPOSER_ALLOW_SUPERUSER=1; \
    composer install --no-interaction --no-plugins --no-scripts --prefer-dist; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/app/output \
    && chmod -R 755 /var/www/html/tmp

EXPOSE 80
