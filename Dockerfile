FROM php:8.1-apache
COPY . /var/www/html/
RUN a2enmod rewrite
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
EXPOSE 80
CMD ["apache2-foreground"]