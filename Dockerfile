FROM php:8.1-apache

# Installation des dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    git \
    unzip \
    zip \
    curl \
    wget \
    ca-certificates \
    gnupg \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Configuration et installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    intl \
    zip \
    gd \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    opcache \
    mbstring \
    exif \
    xml

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration d'Apache
RUN a2enmod rewrite headers
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride None\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Configuration de PHP pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers du projet en plusieurs étapes pour optimiser le cache Docker
# D'abord, copiez composer.json et composer.lock pour installer les dépendances
COPY composer.json composer.lock ./

# Installation des dépendances sans les scripts - cela permet de mettre en cache cette étape
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --prefer-dist --no-dev --optimize-autoloader --no-autoloader --no-scripts --no-interaction

# Copiez le reste des fichiers du projet
COPY . .

# Génération de l'autoloader et exécution des scripts d'installation
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && COMPOSER_ALLOW_SUPERUSER=1 php -d memory_limit=-1 bin/console cache:clear --env=prod --no-debug || echo "Cache clear failed, continuing..."

# Préparation du répertoire var et mise à jour des permissions
RUN mkdir -p var/cache var/log \
    && chmod -R 777 var \
    && chown -R www-data:www-data var

# Script d'entrée pour les opérations de démarrage
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Exposition du port
EXPOSE 80

# Point d'entrée pour les opérations de démarrage
ENTRYPOINT ["docker-entrypoint"]

# Commande de démarrage
CMD ["apache2-foreground"]