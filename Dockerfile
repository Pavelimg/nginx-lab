FROM php:8.2-fpm

# Устанавливаем системные зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    wget \
    libzip-dev \
    libssl-dev \
    librdkafka-dev \
    && pecl install rdkafka \
    && docker-php-ext-enable rdkafka \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    zip \
    sockets \
    pcntl

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Копируем зависимости и устанавливаем их
COPY ./www/composer.json /var/www/html/
RUN composer install --no-dev --optimize-autoloader

# Копируем приложение
COPY ./www /var/www/html

CMD ["php-fpm"]