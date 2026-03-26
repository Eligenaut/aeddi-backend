FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip curl libonig-dev libzip-dev zip \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

EXPOSE 8080

CMD ["sh", "-c", "php artisan optimize && php artisan storage:link && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
