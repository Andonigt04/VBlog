FROM php:8.3-cli AS php-builder

RUN ( curl -sSLf https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o - || echo 'return 1' ) | sh -s \
    xdebug pdo pdo_mysql mysqli openssl fileinfo curl sqlite3 zip gd bcmath gd zip

WORKDIR /var/www/html

FROM composer:lts AS composer

WORKDIR /var/www/html
COPY . .

RUN composer validate
RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

FROM node:23-alpine AS node-builder

WORKDIR /var/www/html
COPY . .

RUN npm ci && npm run build
RUN ls -la /var/www/html/public/build

FROM php:8.3-fpm

WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt update && apt install -y \
    libpng-dev \
    libzip-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    gd \
    zip \
    pdo_mysql \
    mysqli \
    bcmath

COPY --from=composer /var/www/html /var/www/html
COPY --from=node-builder /var/www/html/public /var/www/html/public

RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

RUN chmod 777 -R /var/www/html

RUN apt update \
    && apt install -y default-mysql-client

EXPOSE 9000

CMD ["php-fpm"]