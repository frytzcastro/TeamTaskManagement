# syntax=docker/dockerfile:1

# -----------------------------------------------------------------------------
# Stage 1: PHP dependencies (also needed by Vite for Ziggy)
# -----------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# -----------------------------------------------------------------------------
# Stage 2: frontend assets (Vite)
# -----------------------------------------------------------------------------
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY vite.config.js postcss.config.js tailwind.config.js jsconfig.json ./
COPY resources ./resources
COPY public ./public
# app.js imports ../../vendor/tightenco/ziggy
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy

RUN npm run build

# -----------------------------------------------------------------------------
# Stage 3: runtime (nginx + php-fpm + supervisor)
# -----------------------------------------------------------------------------
FROM php:8.2-fpm-bookworm AS runtime

ENV APP_HOME=/var/www/html \
    RUN_MIGRATIONS=false

WORKDIR ${APP_HOME}

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/log/supervisor

# Application code (without host vendor/node_modules — rebuilt in stages)
COPY --chown=www-data:www-data . ${APP_HOME}
COPY --from=vendor --chown=www-data:www-data /app/vendor ${APP_HOME}/vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ${APP_HOME}/public/build

RUN mkdir -p \
      storage/app/public \
      storage/app/private \
      storage/framework/cache/data \
      storage/framework/sessions \
      storage/framework/views \
      storage/logs \
      bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
  CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
