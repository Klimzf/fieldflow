FROM php:8.5-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g www-data www-data

WORKDIR /var/www/api

EXPOSE 9000

CMD ["php-fpm"]