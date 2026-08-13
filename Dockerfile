FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        pdo_mysql \
        pgsql \
        pdo_pgsql \
        soap \
        zip \
    && a2enmod expires headers rewrite \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/apache-moodle.conf /etc/apache2/sites-available/000-default.conf
COPY docker/moodle.ini /usr/local/etc/php/conf.d/moodle.ini
COPY docker/config.php /usr/local/share/moodle-config.php
COPY docker/moodle-entrypoint.sh /usr/local/bin/moodle-entrypoint

RUN chmod +x /usr/local/bin/moodle-entrypoint

WORKDIR /var/www/moodle

ENTRYPOINT ["moodle-entrypoint"]
CMD ["apache2-foreground"]
