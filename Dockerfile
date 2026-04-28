# syntax=docker/dockerfile:1
FROM php:8.4-apache

ARG WWWUSER=1001
ARG WWWGROUP=1001

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=Europe/Paris

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Extensions système + PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl zip unzip nano cron supervisor \
        libpng-dev libonig-dev libxml2-dev libzip-dev libicu-dev \
        libcurl4-openssl-dev libjpeg-dev libfreetype6-dev \
        libmagickwand-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql mbstring xml zip exif pcntl gd bcmath intl opcache \
    && pecl install redis imagick \
    && docker-php-ext-enable redis imagick \
    && apt-get -y autoremove && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 + pnpm
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm \
    && rm -rf /var/lib/apt/lists/*

# Locale française
RUN apt-get update && apt-get install -y locales \
    && sed -i '/fr_FR.UTF-8/s/^# //g' /etc/locale.gen \
    && locale-gen \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache
RUN a2enmod rewrite
COPY .docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

# PHP
COPY .docker/php.ini /usr/local/etc/php/conf.d/99-app.ini

# Cron (Laravel scheduler)
COPY .docker/crontab /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler

# Permissions
RUN usermod -u ${WWWUSER} www-data && groupmod -g ${WWWGROUP} www-data

# Supervisor
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY .docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

EXPOSE 80

ENTRYPOINT ["entrypoint"]
