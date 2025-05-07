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

# Configuration et installation des extensions PHP avec gestion d'erreurs
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
    AllowOverride All\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Configuration de PHP pour la production avec meilleure gestion d'erreurs
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "display_errors = Off" > /usr/local/etc/php/conf.d/error-logging.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini \
    && echo "error_log = /var/log/apache2/php_errors.log" >> /usr/local/etc/php/conf.d/error-logging.ini

# Définition du répertoire de travail
WORKDIR /var/www/html

# Création du fichier .env.local.php pour la production
RUN mkdir -p .docker

# Copie des fichiers du projet en plusieurs étapes pour optimiser le cache Docker
# D'abord, copiez composer.json et composer.lock pour installer les dépendances
COPY composer.json composer.lock ./

# Installation des dépendances sans les scripts - cela permet de mettre en cache cette étape
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --prefer-dist --no-dev --optimize-autoloader --no-autoloader --no-scripts --no-interaction \
    || echo "Initial composer install failed, will try again after copying all files"

# Copiez le reste des fichiers du projet
COPY . .

# S'assurer que le script d'entrée est exécutable
RUN chmod +x docker-entrypoint.sh

# Génération de l'autoloader et préparation pour la production
RUN COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && php -d memory_limit=-1 -r "file_put_contents('.env.local.php', '<?php return ' . var_export(array_merge(['APP_ENV' => 'prod', 'APP_DEBUG' => '0'], $_ENV), true) . ';');" \
    || echo "Failed to create .env.local.php, continuing anyway"

# Préparation du répertoire var et mise à jour des permissions
RUN mkdir -p var/cache var/log \
    && chmod -R 777 var \
    && chown -R www-data:www-data var

# Exposer le port
EXPOSE 80

# Point d'entrée pour les opérations de démarrage
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]

# Commande de démarrage
CMD ["apache2-foreground"]