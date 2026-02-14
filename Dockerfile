# Utiliser PHP 8.2 CLI
FROM php:8.2-cli

# Installer les extensions nécessaires
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    unzip \
    curl \
    git \
    && docker-php-ext-install pdo_mysql mbstring zip

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier tout le projet
WORKDIR /var/www
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader

# Exposer le port utilisé par Render
EXPOSE 10000

# Démarrer Laravel
CMD php artisan serve --host 0.0.0.0 --port 10000
