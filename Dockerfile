FROM php:8.1-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    unzip \
    zip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    intl \
    opcache \
    pdo \
    pdo_mysql \
    zip \
    gd

# Installation de Composer correctement
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installation de Node.js et npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -
RUN apt-get install -y nodejs

# Configuration d'Apache
RUN a2enmod rewrite
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers du projet
COPY . .

# Installation des dépendances PHP
RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction

# Nettoyage du cache
RUN php bin/console cache:clear --env=prod --no-debug

# Correction des permissions
RUN chown -R www-data:www-data var

# Exposition du port
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]