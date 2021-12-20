FROM php:apache

# Enable "mod_headers" – http://httpd.apache.org/docs/current/mod/mod_headers.html
RUN a2enmod headers
#RUN a2enmod rewrite

ENV TTNMAPPER_HOME=/opt/ttnmapper
ENV APACHE_DOCUMENT_ROOT=/opt/ttnmapper/web

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf


RUN apt-get update && apt-get install -y \
    git libpq-dev \
 && rm -rf /var/lib/apt/lists/*

# Install PHP "pdo" extension with "mysql", "pgsql", "sqlite" drivers – http://php.net/manual/pl/book.pdo.php
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo pgsql pdo_pgsql

WORKDIR /opt/ttnmapper
COPY . /opt/ttnmapper
RUN git submodule update --init