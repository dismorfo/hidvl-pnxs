# Usage example
#
# 1) Build container image
# $ docker build -t dismorfo/hidvl:latest .
# 2) Run container
# $ docker run -t --name=dismorfo-hidvl -p 5000:80 dismorfo/hidvl:latest

FROM php:7.2.5-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install unzip utility and libs needed by zip PHP extension 
RUN apt-get update && apt-get install -y \
    zlib1g-dev \
    libzip-dev \
    unzip

RUN docker-php-ext-install zip

COPY . /var/www/html/

# Install
RUN cd /var/www/html/ \
    && /usr/bin/composer install
