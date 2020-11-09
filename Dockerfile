# Usage example
#
# 1) Build container image
# $ docker build -t dismorfo/hidvl:latest .
# 2) Run container
# $ docker run -t --name=dismorfo-hidvl -p 8080:80 dismorfo/hidvl:latest

FROM php:7.1-apache
COPY . /var/www/html/
