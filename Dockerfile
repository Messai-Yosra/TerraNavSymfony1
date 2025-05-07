FROM php:8.1-apache

# Installation des dépendances système nécessaires pour votre application
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

# Configuration et installation des extensions PHP nécessaires
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
    xml \
    && docker-php-ext-enable opcache

# Configuration de la timezone pour PHP
RUN echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/timezone.ini

# Installation de Composer avec vérification
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer --version

# Configuration d'Apache
RUN a2enmod rewrite headers
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Création d'une configuration Apache personnalisée pour Symfony
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride None\n\
    Require all granted\n\
    FallbackResource /index.php\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Configuration du PHP pour la production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers du projet (sauf ceux dans .dockerignore)
COPY . .

# Modification des permissions sur le dossier vendor s'il existe déjà
RUN if [ -d "vendor" ]; then chmod -R 777 vendor; fi

# Installation des dépendances PHP avec Composer
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction \
    || (echo "Composer installation failed" && exit 1)

# Nettoyage du cache et préparation de l'application pour la production
RUN APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear --no-debug \
    && APP_ENV=prod APP_DEBUG=0 php bin/console assets:install public \
    && chmod -R 777 var \
    && chown -R www-data:www-data var

# Suppression des répertoires inutiles en production
RUN rm -rf tests/ .env.test phpunit.xml.dist

# Définition d'un script de démarrage pour exécuter des tâches supplémentaires si nécessaire
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

# Exposition du port
EXPOSE 80

# Point d'entrée pour exécuter des scripts de démarrage si nécessaire
ENTRYPOINT ["docker-entrypoint"]

# Commande de démarrage
CMD ["apache2-foreground"]