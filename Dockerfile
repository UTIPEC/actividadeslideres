FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2en_mod rewrite
COPY ./src /var/www/html/
RUN chown -R www-data:www-data /var/www/html