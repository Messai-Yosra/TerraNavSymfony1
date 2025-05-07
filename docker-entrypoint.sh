#!/bin/bash
set -e

# Fonction pour afficher les messages de debug
debug() {
    echo "[ENTRYPOINT DEBUG] $1"
}

# Fonction pour attendre que la base de données soit prête
wait_for_db() {
    # Si DATABASE_URL est défini, on attend que la base de données soit prête
    if [ ! -z "$DATABASE_URL" ]; then
        debug "Waiting for database to be ready..."
        
        # Extrait les infos de connexion de DATABASE_URL
        if [[ $DATABASE_URL == *"mysql://"* ]]; then
            DB_TYPE="mysql"
            debug "Database type: MySQL"
        elif [[ $DATABASE_URL == *"postgresql://"* || $DATABASE_URL == *"postgres://"* ]]; then
            DB_TYPE="pgsql"
            debug "Database type: PostgreSQL"
        else
            debug "Database type not supported for auto-wait. Will proceed without waiting."
            return 0
        fi
        
        ATTEMPTS=0
        MAX_ATTEMPTS=30
        SLEEP_TIME=2
        
        # Tentative de connexion à la base de données avec gestion d'erreur améliorée
        until php -r "try {
            \$dbUrl = getenv('DATABASE_URL');
            debug('Using DB URL: ' . \$dbUrl);
            \$dbParams = parse_url(\$dbUrl);
            \$dbHost = \$dbParams['host'] ?? 'localhost';
            \$dbPort = \$dbParams['port'] ?? '';
            \$dbName = trim(\$dbParams['path'] ?? '', '/');
            \$dbUser = \$dbParams['user'] ?? '';
            \$dbPass = \$dbParams['pass'] ?? '';
            
            \$dsn = '$DB_TYPE:host=' . \$dbHost;
            if (\$dbPort) {
                \$dsn .= ';port=' . \$dbPort;
            }
            if (\$dbName) {
                \$dsn .= ';dbname=' . \$dbName;
            }
            
            debug('DSN: ' . \$dsn);
            debug('User: ' . \$dbUser);
            
            \$dbh = new PDO(\$dsn, \$dbUser, \$dbPass);
            \$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo 'Database connection successful!';
        } catch(PDOException \$e) {
            echo 'Database connection error: ' . \$e->getMessage();
            exit(1);
        } catch(Exception \$e) {
            echo 'Error: ' . \$e->getMessage();
            exit(1);
        }
        function debug(\$msg) {
            echo '[PHP DEBUG] ' . \$msg . PHP_EOL;
        }" || [ $ATTEMPTS -eq $MAX_ATTEMPTS ];
        do
            debug "Waiting for database connection... ($ATTEMPTS/$MAX_ATTEMPTS)"
            ATTEMPTS=$((ATTEMPTS+1))
            sleep $SLEEP_TIME
        done
        
        if [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; then
            debug "Could not connect to database after $MAX_ATTEMPTS attempts!"
            debug "Will continue startup, but application may not work properly."
        fi
    else
        debug "No DATABASE_URL defined, skipping database wait."
    fi
}

# Fonction pour exécuter les migrations si nécessaires
run_migrations() {
    if [ ! -z "$DATABASE_URL" ]; then
        debug "Running database migrations..."
        
        # D'abord, vérifier si la base de données existe et est accessible
        if php bin/console doctrine:migrations:status --no-interaction &> /dev/null; then
            debug "Database is accessible, running migrations..."
            # Exécuter les migrations avec options de tolérance aux erreurs
            php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || debug "Migration command returned an error, but continuing..."
        else
            debug "Could not access migrations status. Creating schema instead..."
            # Essayer de créer le schéma de base de données à la place
            php bin/console doctrine:schema:create --no-interaction || debug "Schema creation failed, but continuing..."
        fi
    else
        debug "No DATABASE_URL defined, skipping migrations."
    fi
}

# Fonction pour initialiser l'application
init_app() {
    debug "Initializing application..."
    
    # Mise à jour des permissions si nécessaire
    if [ ! -d "var/cache" ]; then
        debug "Creating var/cache directory..."
        mkdir -p var/cache
    fi
    
    if [ ! -d "var/log" ]; then
        debug "Creating var/log directory..."
        mkdir -p var/log
    fi
    
    # S'assurer que var et ses sous-répertoires sont accessibles en écriture
    debug "Updating permissions on var directory..."
    chmod -R 777 var
    chown -R www-data:www-data var
    
    # Vider le cache
    debug "Clearing cache..."
    php bin/console cache:clear --no-debug --env=prod || debug "Cache clear failed, but continuing..."
    
    # Réchauffer le cache
    debug "Warming up cache..."
    php bin/console cache:warmup --no-debug --env=prod || debug "Cache warmup failed, but continuing..."
    
    # Correction des permissions après les opérations de cache
    debug "Re-setting permissions after cache operations..."
    chmod -R 777 var
    chown -R www-data:www-data var
    
    # Vérifier si Apache est configuré correctement
    debug "Checking if mod_rewrite is enabled..."
    if ! apache2ctl -M 2>/dev/null | grep -q "rewrite_module"; then
        debug "mod_rewrite is not enabled. Enabling it now..."
        a2enmod rewrite
    fi
    
    # Afficher l'état de l'application
    debug "Application environment: $APP_ENV"
    debug "Debug mode: $APP_DEBUG"
    debug "Application initialization complete."
}

# Exécution des fonctions d'initialisation avec gestion d'erreurs
debug "Starting application initialization..."
wait_for_db || debug "Database connection setup failed, but continuing..."
run_migrations || debug "Migrations failed, but continuing..."
init_app || debug "App initialization had errors, but continuing..."
debug "Initialization complete, starting Apache..."

# Exécution de la commande passée en argument (généralement apache2-foreground)
exec "$@"