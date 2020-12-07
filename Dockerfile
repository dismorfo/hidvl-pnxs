# Usage example
#
# 1) Build container image
# $ docker build -t dismorfo/hidvl:latest .
# 2) Run container
# $ docker run -t --name=dismorfo-hidvl -p 5000:80 dismorfo/hidvl:latest
#
# Try it out http://localhost:5000/hidvl/47d7wmjw

FROM php:5.3-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /var/www/html/

# Install unzip utility and install dependencies
RUN apt-get update && \
    apt-get install -y  --force-yes unzip && \
    cd /var/www/html/ && \
    /usr/bin/composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader
