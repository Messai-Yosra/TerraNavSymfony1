#!/bin/bash
set -e

# Fonction pour attendre que la base de données soit prête
wait_for_db() {
    # Si DATABASE_URL est défini, on attend que la base de données soit prête
    if [ ! -z "$DATABASE_URL" ]; then
        echo "Waiting for database to be ready..."
        
        # Extrait les infos de connexion de DATABASE_URL
        if [[ $DATABASE_URL == *"mysql://"* ]]; then
            DB_TYPE="mysql"
        elif [[ $DATABASE_URL == *"postgresql://"* ]]; then
            DB_TYPE="pgsql"
        else
            echo "Database type not supported for auto-wait. Will proceed without waiting."
            return 0
        fi
        
        ATTEMPTS=0
        MAX_ATTEMPTS=30
        SLEEP_TIME=2
        
        # Tentative de connexion à la base de données
        until php -r "try { 
            \$dbh = new PDO('$DB_TYPE:host=\$_SERVER[\"DB_HOST\"];dbname=\$_SERVER[\"DB_NAME\"]', 
                            \$_SERVER[\"DB_USER\"], 
                            \$_SERVER[\"DB_PASSWORD\"]); 
                echo 'Database connection successful!'; 
            } catch(PDOException \$e) { 
                echo \$e->getMessage(); 
                exit(1); 
            }" || [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; 
        do
            echo "Waiting for database connection... ($ATTEMPTS/$MAX_ATTEMPTS)"
            ATTEMPTS=$((ATTEMPTS+1))
            sleep $SLEEP_TIME
        done
        
        if [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; then
            echo "Could not connect to database after $MAX_ATTEMPTS attempts!"
            echo "Will continue startup, but application may not work properly."
        fi
    else
        echo "No DATABASE_URL defined, skipping database wait."
    fi
}

# Fonction pour exécuter les migrations si nécessaires
run_migrations() {
    if [ "$APP_ENV" = "prod" ] && [ ! -z "$DATABASE_URL" ]; then
        echo "Running database migrations..."
        php bin/console doctrine:migrations:migrate --no-interaction || echo "Migration failed, but continuing."
    fi
}

# Fonction pour initialiser l'application
init_app() {
    echo "Initializing application..."
    
    # Mise à jour des permissions si nécessaire
    if [ ! -d "var/cache" ]; then
        mkdir -p var/cache
    fi
    
    if [ ! -d "var/log" ]; then
        mkdir -p var/log
    fi
    
    chmod -R 777 var/cache var/log
    chown -R www-data:www-data var
    
    # Installation des assets si nécessaire
    if [ ! -f "public/build/manifest.json" ] && [ -f "package.json" ]; then
        echo "Assets not found, installing..."
        # Si vous avez besoin de construire des assets JavaScript
        # npm ci && npm run build || echo "Asset build failed, but continuing."
    fi
}

# Exécution des fonctions d'initialisation
wait_for_db
run_migrations
init_app

# Exécution de la commande passée en argument
exec "$@"