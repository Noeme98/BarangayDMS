FROM php:8.1-apache
RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl
COPY . /var/www/html/
RUN a2enmod rewrite
EXPOSE 80
CMD ["apache2-foreground"]