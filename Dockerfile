# Usage example
#
# 1) Build container image
# $ docker build -t dismorfo/hidvl:latest .
# 2) Run container
# $ docker run -t --name=dismorfo-hidvl -p 5050:80 dismorfo/hidvl:latest
#
# Try it out http://localhost:5050/47d7wmjw

FROM php:8-apache

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install unzip utility (required by composer to extract packages)
RUN apt-get update && \
    apt-get install -y --no-install-recommends unzip && \
    rm -rf /var/lib/apt/lists/*

# Enable the modules .htaccess relies on and allow .htaccess overrides,
# otherwise Apache ignores the app's front-controller rewrite rules.
RUN a2enmod rewrite headers && \
    { \
      echo '<Directory /var/www/html>'; \
      echo '  AllowOverride All'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/hidvl-overrides.conf && \
    a2enconf hidvl-overrides

WORKDIR /var/www/html

# Install dependencies first so this layer is cached unless composer files change
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

# Copy the rest of the application
COPY . .

# Apache runs as www-data; it needs write access to write cache files
RUN chown -R www-data:www-data cache
