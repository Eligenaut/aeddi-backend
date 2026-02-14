# PHP 8.2 CLI + Apache si besoin
FROM php:8.2-cli

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libonig-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install pdo_mysql mbstring zip

# Installer Composer globalement
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le projet Laravel
WORKDIR /var/www
COPY . .

# Installer les dépendances Laravel
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Générer la clé si nécessaire (optionnel)
# RUN php artisan key:generate

# Exposer le port
EXPOSE 10000

# Démarrer Laravel
CMD php artisan serve --host 0.0.0.0 --port 10000
