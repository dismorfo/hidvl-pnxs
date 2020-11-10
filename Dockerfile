# Usage example
#
# 1) Build container image
# $ docker build -t dismorfo/hidvl:latest .
# 2) Run container
# $ docker run -t --name=dismorfo-hidvl -p 5000:80 dismorfo/hidvl:latest
# 
# Try it out http://localhost:5000/hidvl/47d7wmjw

FROM php:7.2.5-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

# Install unzip utility and install dependencies
RUN apt-get update && \
    apt-get install -y unzip && \
    cd /var/www/html/ && \
    /usr/bin/composer install
