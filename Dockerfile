
FROM php:8.2-apache
RUN apt-get update && apt-get install -y libsqlite3-dev
RUN docker-php-ext-install pdo pdo_sqlite
WORKDIR /var/www/html
COPY . /var/www/html/
RUN chown www-data:www-data bsap.db && chmod 664 bsap.db
RUN a2enmod rewrite
