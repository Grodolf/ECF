# Vite & Gourmand

Projet ECF (Évaluation en Cours de Formation) — Titre professionnel **Développeur Web et Web Mobile**.

Application web de commande de menus traiteur à domicile, développée en PHP 8.2 sans framework.

---

## Stack technique

| Couche | Technologie |
| --- | --- |
| Serveur | PHP 8.2 + Apache |
| Base de données relationnelle | MariaDB |
| Base de données documents | MongoDB |
| Envoi d'emails | PHPMailer |
| Calcul distance livraison | OpenRouteService API |
| Gestion des dépendances | Composer |
| CSS | Vanilla (mobile-first, thème clair/sombre/auto) |
| JS | ES Modules natifs |
| Visualisations | Plotly.js |

---

## Fonctionnalités

- Catalogue de menus avec filtres (thème, régime, prix, personnes)
- Commande en ligne avec calcul de frais de livraison (distance routière depuis Bordeaux)
- Espace client : suivi commandes, modification, annulation, avis
- Espace employé : gestion commandes, plats, menus, horaires, modération des avis
- Espace admin : gestion des employés, tableau de bord des ventes (MongoDB)
- Authentification sécurisée : sessions, CSRF, bcrypt, réinitialisation de mot de passe
- Emails transactionnels (confirmation, changement de statut, validation d'avis)

---

## Environnement de développement

L'environnement de dev tourne intégralement dans Docker via `_docker/`.

### Prérequis

- Docker + Docker Compose
- Composer (ou utilisation via le container)

### Services

| Service | Container | Accès local |
| --- | --- | --- |
| Application PHP + Apache | `ECF-web` | <http://localhost:9080> |
| MariaDB | `ECF-mariadb` | `localhost:9033` |
| MongoDB | `ECF-mongodb` | `localhost:9017` |
| phpMyAdmin | `ECF-phpmyadmin` | <http://localhost:9081> |
| MailHog (SMTP de test) | `ECF-mailhog` | <http://localhost:9025> |

### Démarrage

```bash
# 1. Copier et remplir le fichier de configuration
cp config/.env.example config/.env

# 2. Lancer les containers (depuis _docker/)
cd _docker
docker compose up -d

# 3. Initialiser la base de données (depuis le container)
docker exec -it ECF-mariadb mysql -u root -p < ../scripts/create_database.sql

# 4. Installer les dépendances PHP
docker exec -it ECF-web composer install
```

> Les emails envoyés par l'application sont capturés par MailHog — aucun email réel n'est expédié.  
> Consulter l'interface : <http://localhost:9025>

### Variables d'environnement (`config/.env`)

```ini
# Application
APP_ENV=development
APP_URL=http://localhost:9080

# MariaDB
MARIADB_ROOT_PASSWORD=
MARIADB_DATABASE=vite_et_gourmand
MARIADB_USER=
MARIADB_PASSWORD=

# MongoDB
MONGO_ROOT_USER=
MONGO_ROOT_PASSWORD=
MONGO_DATABASE=vite_et_gourmand

# SMTP — MailHog en dev
SMTP_HOST=mailhog
SMTP_PORT=1025
SMTP_USE_AUTH=false
SMTP_USER=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=contact@vite-et-gourmand.fr
SMTP_FROM_NAME=Vite & Gourmand
SMTP_DEBUG=false

# OpenRouteService (calcul distance livraison)
ORS_API_KEY=
```

> Documentation complète de l'environnement Docker : [`_docker/README.md`](_docker/README.md)

---

## Environnement de production

| Service | Fournisseur |
| --- | --- |
| Hébergement applicatif | [Fly.io](https://fly.io) — région `cdg` (Paris) |
| MariaDB | [Aiven](https://aiven.io) — free tier |
| MongoDB | [MongoDB Atlas](https://www.mongodb.com/atlas) — free tier |
| SMTP | [Mailtrap](https://mailtrap.io) |

**URL de production :** <https://ecf.fly.dev>

### Configuration Fly.io

Le déploiement utilise le `Dockerfile` à la racine du projet. La configuration est dans [`fly.toml`](fly.toml).

- HTTPS forcé, machine auto-stop/start
- Volume persistant monté sur `/var/www/html/public/uploads` pour les images uploadées
- Health check sur `/health.php` toutes les 30 secondes

### Déploiement

```bash
# Installer le CLI Fly.io puis :
fly deploy
```

### Variables d'environnement en production

À configurer via `fly secrets set` ou l'interface Fly.io :

```bash
fly secrets set DB_HOST=<aiven-host> \
               DB_NAME=vite_et_gourmand \
               DB_USER=<user> \
               DB_PASS=<password> \
               MONGO_HOST=<atlas-host> \
               MONGO_URI=<uri> \
               MONGO_DATABASE=vite_et_gourmand \
               SMTP_HOST=smtp.mailtrap.io \
               SMTP_PORT=587 \
               SMTP_USE_AUTH=true \
               SMTP_USER=rapi \
               SMTP_PASSWORD=<api-key> \
               SMTP_FROM_EMAIL=noreply@ecf.rodweb.dev \
               ORS_API_KEY=<key>
```

> La connexion MariaDB en production utilise SSL avec le certificat Aiven (`config/ssl/aiven-ca.pem`), activé automatiquement lorsque `APP_ENV=production`.

---

## Structure du projet

```txt
ECF/
├── _docker/                  Environnement de développement Docker
│   ├── docker-compose.yml
│   ├── Dockerfile            Image PHP 8.2 + Apache (dev)
│   ├── config/               php.ini, .env
│   └── README.md
├── _documentation/
│   ├── design /              mockups et wireframes
│   ├── MCD_MLD.md
│   ├── Mode d'emploi.pdf
│   └── PROJECT.md
├── config/
│   └── ssl/                  Certificat CA Aiven (prod)
├── public/                   DocumentRoot
│   ├── site.webmanifest      Favicons
│   ├── index.php             Point d'entrée unique
│   ├── health.php            Health check Fly.io
│   ├── css/
│   ├── js/
│   ├── img/
│   └── uploads/              Images menus et plats (persistées)
├── scripts/
│    ├── create_database.sql  Schéma + données initiales
│    ├── test_data.sql        Données fictives pour test
│    └── seed_mongodb.php     script d'insertion de données pour les graphiques
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Core/                 Router, Session, Security, Mailer…
│   └── Config/               Database, Smtp, MongoDB
├── templates/                layout.php, partials/, emails/
├── views/                    auth/, user/, menus/, orders/, employe/, admin/
├── vendor/                   Dépendances Composer
├── Dockerfile                Image de production
├── fly.toml                  Configuration Fly.io
└── composer.json
```

> Documentation technique détaillée (architecture, routes, contrôleurs, modèles, CSS, JS) : [`_documentation/PROJECT.md`](_documentation/PROJECT.md)

---

## Compte administrateur par défaut

| Champ | Valeur |
| --- | --- |
| Email | `admin@vite-et-gourmand.fr` |
| Mot de passe | Dans le document **"Copie à rendre"** |

> À changer immédiatement après la première connexion en production.
