FROM php:8.3-fpm

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev libpq-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql mbstring bcmath gd zip exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

# Copy custom php.ini/uploads.ini settings
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini


RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]
