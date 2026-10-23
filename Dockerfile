FROM php:8.4-cli

WORKDIR /app

RUN echo 'date.timezone = America/Sao_Paulo' > /usr/local/etc/php/conf.d/fuso.ini \
    && apt-get update -yqq \
    && apt-get install -yqq --no-install-recommends git unzip libicu-dev \
    && docker-php-ext-install intl \
    && git config --global --add safe.directory /app \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer


CMD ["php", "-v"]
