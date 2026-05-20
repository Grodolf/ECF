# Environnement Docker — Vite & Gourmand

## Vue d'ensemble

Stack de développement containerisée avec 5 services :

| Service | Container | Port local | Description |
| ------- | --------- | ---------- | ----------- |
| **web** | ECF-web | 9080 | PHP 8.2 + Apache |
| **db** | ECF-mariadb | 9033 | MariaDB (Bitnami) |
| **mongodb** | ECF-mongodb | 9017 | MongoDB (Bitnami) |
| **phpmyadmin** | ECF-phpmyadmin | 9081 | Interface web MariaDB |
| **mailhog** | ECF-mailhog | 9025 / 9026 | Serveur SMTP de test |

## Accès rapides

- **Application** : <http://localhost:9080>
- **phpMyAdmin** : <http://localhost:9081>
- **MailHog (interface)** : <http://localhost:9025>
- **MailHog (SMTP)** : localhost:9026

## Prérequis

- Docker et Docker Compose
- Fichier `config/.env` configuré (copier `.env.example`)

## Démarrage

```bash
# Depuis le dossier _docker/
docker compose up -d

# Voir les logs
docker compose logs -f

# Arrêter
docker compose down
```

## Architecture des fichiers

```txt
_docker/
├── docker-compose.yml    # Orchestration des services
├── Dockerfile            # Image PHP 8.2 + Apache
├── config/
│   └── php.ini           # Configuration PHP personnalisée
└── README.md             # Ce fichier
```

## Service Web (PHP + Apache)

### Image de base

`php:8.2-apache`

### Extensions PHP installées

- **pdo** / **pdo_mysql** / **mysqli** — Connexion MariaDB
- **mongodb** — Connexion MongoDB (via PECL)
- **gd** — Manipulation d'images (avec FreeType + JPEG)
- **zip** — Compression/décompression
- **intl** — Internationalisation

### Modules Apache activés

- `rewrite` — Réécriture d'URL (.htaccess)
- `headers` — En-têtes HTTP personnalisés
- `ssl` — Support HTTPS (préparé)

### Configuration PHP (`config/php.ini`)

```ini
# Limites
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300

# Erreurs (dev)
display_errors = On
error_reporting = E_ALL

# Timezone
date.timezone = Europe/Paris

# Sessions sécurisées
session.cookie_httponly = 1
session.cookie_samesite = "Strict"
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_lifetime = 3600
```

### Volume monté

Le projet est monté dans `/var/www/html` avec DocumentRoot pointant vers `/var/www/html/public`.

## Service MariaDB

### Image

`bitnami/mariadb:latest`

### Connexion depuis PHP

```php
$host = 'db';        // Nom du service Docker
$port = 3306;        // Port interne
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
```

### Connexion externe (client SQL)

```txt
Host: localhost
Port: 9033
User: voir .env (MARIADB_USER)
Pass: voir .env (MARIADB_PASSWORD)
```

### Persistence

Volume Docker `mariadb_data` — les données persistent après `docker compose down`.

## Service MongoDB

### Image mongodb

`bitnami/mongodb:latest`

### Connexion pour PHP

```php
$uri = "mongodb://{$user}:{$pass}@mongodb:27017";
$client = new MongoDB\Client($uri);
```

### Connexion externe

```txt
Host: localhost
Port: 9017
User: voir .env (MONGO_ROOT_USER)
Pass: voir .env (MONGO_ROOT_PASSWORD)
```

### Persistences

Volume Docker `mongo_data`.

## Service MailHog

Serveur SMTP de test — capture tous les emails sans les envoyer.

### Intégration PHPMailer

Le projet utilise **PHPMailer** (installé via Composer) avec la classe `App\Core\Mailer` :

```php
// Envoi avec template HTML
$mailer = new Mailer();
$mailer->sendWithTemplate(
    $email,                    // Destinataire
    "$prenom $nom",            // Nom destinataire
    'Bienvenue !',             // Sujet
    'welcome',                 // Template (templates/emails/welcome.php)
    ['prenom' => $prenom, ...]  // Variables du template
);

// Envoi direct HTML + texte
$mailer->send($email, $name, $subject, $htmlBody, $textBody);
```

La configuration SMTP est lue depuis les variables d'environnement via `App\Config\Smtp`.

### Voir les emails

Ouvrir <http://localhost:9025> — tous les emails envoyés apparaissent ici.

## Variables d'environnement

Le fichier `config/.env` est chargé par Docker Compose :

```ini
# Environnement
APP_ENV=development
APP_URL=http://localhost:9080

# MariaDB
MARIADB_HOST=db
MARIADB_PORT=3306
MARIADB_ROOT_PASSWORD=xxx
MARIADB_DATABASE=ecf_db
MARIADB_USER=ecf_user
MARIADB_PASSWORD=xxx

# MongoDB
MONGO_ROOT_USER=admin
MONGO_ROOT_PASSWORD=xxx

# SMTP (MailHog)
SMTP_HOST=mailhog
SMTP_PORT=1025
SMTP_USE_AUTH=false
SMTP_USER=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=contact@viteetgourmand.fr
SMTP_FROM_NAME=Vite & Gourmand
SMTP_DEBUG=false
```

> Les variables `SMTP_HOST` et `SMTP_PORT` sont passées au container web via `docker-compose.yml`.
> Les autres variables SMTP sont lues directement depuis le fichier `.env` par PHP.

## Commandes utiles

```bash
# Rebuild après modification du Dockerfile
docker compose build --no-cache

# Accéder au shell du container web
docker exec -it ECF-web bash

# Accéder à MariaDB en CLI
docker exec -it ECF-mariadb mysql -u root -p

# Accéder à MongoDB en CLI
docker exec -it ECF-mongodb mongosh -u admin -p

# Voir les volumes
docker volume ls

# Supprimer les volumes (ATTENTION: perte de données)
docker compose down -v
```

## Dépendances PHP (Composer)

Le projet utilise Composer pour gérer les dépendances PHP :

```bash
# Installer les dépendances (depuis le container web)
docker exec -it ECF-web composer install

# Ou depuis le dossier projet en local
composer install
```

### Packages installés

| Package | Version | Usage |
| ------- | ------- | ----- |
| **phpmailer/phpmailer** | ^7.0 | Envoi d'emails via SMTP |

## Passage en production

1. Modifier `APP_ENV=production` dans `.env`
2. Dans `php.ini`, désactiver `display_errors`
3. Activer `session.cookie_secure = 1` (HTTPS requis)
4. Configurer un vrai serveur SMTP au lieu de MailHog (`SMTP_USE_AUTH=true` + identifiants)
5. Utiliser des secrets Docker pour les mots de passe
