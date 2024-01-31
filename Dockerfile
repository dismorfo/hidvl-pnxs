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

# Install unzip utility and install dependencies
RUN apt-get update && \
    apt-get install -y unzip
