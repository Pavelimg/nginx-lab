FROM php:8.2-fpm

# Устанавливаем расширения для работы с MySQL
RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html
COPY ./www /var/www/html

CMD ["php-fpm"]