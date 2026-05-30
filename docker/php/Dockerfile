FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    unzip \
    zip

RUN docker-php-ext-install \
    bcmath \
    mbstring \
    pdo \
    pdo_pgsql \
    pgsql \
    xml \
    zip \
    && docker-php-ext-enable opcache

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 -S appgroup && \
    adduser -u 1000 -S appuser -G appgroup

COPY --chown=appuser:appgroup . .

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
