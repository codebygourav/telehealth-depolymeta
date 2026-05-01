FROM php:8.3-fpm

WORKDIR /var/www/html

# Install system dependencies + Node.js
RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql mbstring bcmath gd zip exif xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

# Copy project files
COPY . .

# Install PHP deps
RUN composer install --no-interaction --prefer-dist

# 🔥 IMPORTANT: Build Vite assets
RUN npm install && npm run build

RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
