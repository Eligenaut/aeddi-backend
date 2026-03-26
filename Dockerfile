# Dockerfile optimisé

FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Précompiler le cache au build, pas à chaque start
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan storage:link \
    && php artisan migrate --force

EXPOSE 8080

# CMD simplifié : seulement démarrage du serveur
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT:-8080}"]
