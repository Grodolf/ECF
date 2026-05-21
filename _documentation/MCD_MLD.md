# Modèle de Données — Vite & Gourmand

**SGBD :** MariaDB | **Base :** `vite_et_gourmand`

---

## Sommaire

1. [MCD — Modèle Conceptuel de Données](#1-mcd--modèle-conceptuel-de-données)
2. [MLD — Modèle Logique de Données](#2-mld--modèle-logique-de-données)
   - [Domaine Catalogue](#21-domaine-catalogue)
   - [Domaine Commandes](#22-domaine-commandes)
   - [Domaine Utilisateurs & Sécurité](#23-domaine-utilisateurs--sécurité)
3. [Dictionnaire de données](#3-dictionnaire-de-données)
4. [Conventions et notes](#4-conventions-et-notes)

---

## 1. MCD — Modèle Conceptuel de Données

### Cardinalités

| Notation Mermaid | Signification |
| --- | --- |
| `\|\|` | Exactement 1 |
| `\|o` | 0 ou 1 |
| `o{` | 0 ou plusieurs |
| `\|{` | 1 ou plusieurs |

```mermaid
erDiagram
    %% ─── Domaine Catalogue ───
    THEME ||--o{ MENU : "thématise"
    REGIME ||--o{ MENU : "caractérise"
    TYPE_PLAT ||--o{ PLAT : "classifie"
    PLAT o{--o{ ALLERGENE : "contient (0,N / 0,N)"
    MENU o{--o{ PLAT : "compose (0,N / 0,N)"
    MENU ||--o{ IMAGE_MENU : "illustré par"

    %% ─── Domaine Commandes ───
    STATUT_COMMANDE ||--o{ COMMANDE : "définit"
    MENU ||--o{ COMMANDE : "est commandé via"
    COMMANDE ||--|{ HISTORIQUE_STATUT : "journalise"
    STATUT_COMMANDE ||--o{ HISTORIQUE_STATUT : "référence"
    COMMANDE ||--o| AVIS : "fait l'objet de"

    %% ─── Domaine Utilisateurs ───
    UTILISATEUR ||--o{ TOKEN_REINITIALISATION : "possède"
    UTILISATEUR ||--o{ COMMANDE : "passe"
    UTILISATEUR |o--o{ COMMANDE : "annule"
    UTILISATEUR |o--o{ CONTACT : "traite"
    UTILISATEUR ||--o{ AVIS : "rédige"
    UTILISATEUR |o--o{ AVIS : "valide"
    UTILISATEUR |o--o{ HORAIRE : "modifie"
    UTILISATEUR ||--o{ HISTORIQUE_STATUT : "enregistre"

    THEME {
        int id PK
        string name
        text description
    }

    REGIME {
        int id PK
        string name
        text description
    }

    ALLERGENE {
        int id PK
        string name
        text description
    }

    TYPE_PLAT {
        int id PK
        string name
        int display_order
    }

    PLAT {
        int id PK
        string name
        text description
        string image_url
        boolean active
    }

    MENU {
        int id PK
        string title
        text description
        int min_people
        decimal base_price
        text conditions
        int stock
        boolean active
    }

    IMAGE_MENU {
        int id PK
        string image_url
        string alt_text
        int display_order
    }

    STATUT_COMMANDE {
        int id PK
        string name
        text description
        int workflow_order
    }

    COMMANDE {
        int id PK
        int nb_people
        decimal menu_price
        decimal delivery_cost
        decimal total_price
        decimal reduction
        text delivery_address
        string delivery_city
        date delivery_date
        time delivery_time
        boolean material_loaned
        boolean material_returned
        datetime material_return_deadline
        text cancellation_reason
        enum contact_method
    }

    HISTORIQUE_STATUT {
        int id PK
        text comment
        datetime changed_at
    }

    AVIS {
        int id PK
        tinyint rating
        text comment
        enum status
        datetime validated_at
        text reject_reason
    }

    UTILISATEUR {
        char id PK
        string nom
        string prenom
        string email
        string password
        string gsm
        string adresse
        char code_postal
        string city
        enum role
        boolean actif
    }

    TOKEN_REINITIALISATION {
        int id PK
        string token_hash
        datetime expires_at
        boolean used
    }

    CONTACT {
        int id PK
        string email
        string title
        text message
        boolean processed
        datetime processed_at
    }

    HORAIRE {
        int id PK
        enum day_of_week
        time opening_time
        time closing_time
        boolean closed
    }
```

---

## 2. MLD — Modèle Logique de Données

### 2.1 Domaine Catalogue

Gère le référentiel des plats, menus, thèmes, régimes et allergènes.

```mermaid
erDiagram
    themes {
        INT id PK
        VARCHAR(100) name
        TEXT description
        DATETIME created_at
    }

    regimes {
        INT id PK
        VARCHAR(100) name
        TEXT description
        DATETIME created_at
    }

    allergenes {
        INT id PK
        VARCHAR(100) name
        TEXT description
        DATETIME created_at
    }

    dish_types {
        INT id PK
        VARCHAR(50) name
        INT display_order
        DATETIME created_at
    }

    dishes {
        INT id PK
        VARCHAR(200) name
        TEXT description
        INT dish_type_id FK
        VARCHAR(255) image_url
        BOOLEAN active
        DATETIME created_at
        DATETIME updated_at
    }

    dish_allergenes {
        INT dish_id FK
        INT allergene_id FK
    }

    menus {
        INT id PK
        VARCHAR(200) title
        TEXT description
        INT theme_id FK
        INT regime_id FK
        INT min_people
        DECIMAL(10_2) base_price
        TEXT conditions
        INT stock
        BOOLEAN active
        DATETIME created_at
        DATETIME updated_at
    }

    menu_dishes {
        INT menu_id FK
        INT dish_id FK
    }

    menu_images {
        INT id PK
        INT menu_id FK
        VARCHAR(255) image_url
        VARCHAR(255) alt_text
        INT display_order
        DATETIME created_at
    }

    themes ||--o{ menus : "theme_id"
    regimes ||--o{ menus : "regime_id"
    dish_types ||--o{ dishes : "dish_type_id"
    dishes ||--o{ dish_allergenes : "dish_id"
    allergenes ||--o{ dish_allergenes : "allergene_id"
    menus ||--o{ menu_dishes : "menu_id"
    dishes ||--o{ menu_dishes : "dish_id"
    menus ||--o{ menu_images : "menu_id"
```

### 2.2 Domaine Commandes

Gère le cycle de vie des commandes, le suivi des statuts et les avis clients.

```mermaid
erDiagram
    order_status {
        INT id PK
        VARCHAR(100) name
        TEXT description
        INT workflow_order
        DATETIME created_at
    }

    orders {
        INT id PK
        CHAR(36) user_id FK
        INT menu_id FK
        INT nb_people
        DECIMAL(10_2) menu_price
        DECIMAL(10_2) delivery_cost
        DECIMAL(10_2) total_price
        DECIMAL(10_2) reduction
        TEXT delivery_address
        VARCHAR(100) delivery_city
        DATE delivery_date
        TIME delivery_time
        INT status_id FK
        BOOLEAN material_loaned
        BOOLEAN material_returned
        DATETIME material_return_deadline
        TEXT cancellation_reason
        CHAR(36) cancelled_by FK
        ENUM contact_method
        DATETIME created_at
        DATETIME updated_at
    }

    order_status_history {
        INT id PK
        INT order_id FK
        INT status_id FK
        CHAR(36) changed_by FK
        TEXT comment
        DATETIME changed_at
    }

    reviews {
        INT id PK
        INT order_id FK
        CHAR(36) user_id FK
        TINYINT rating
        TEXT comment
        ENUM status
        CHAR(36) validated_by FK
        DATETIME validated_at
        TEXT reject_reason
        DATETIME created_at
        DATETIME updated_at
    }

    users {
        CHAR(36) id PK
        VARCHAR(100) nom
        VARCHAR(100) prenom
        VARCHAR(255) email
        VARCHAR(255) password
        ENUM role
        BOOLEAN actif
    }

    menus {
        INT id PK
        VARCHAR(200) title
        BOOLEAN active
    }

    order_status ||--o{ orders : "status_id"
    menus ||--o{ orders : "menu_id"
    users ||--o{ orders : "user_id"
    users |o--o{ orders : "cancelled_by"
    orders ||--|{ order_status_history : "order_id"
    order_status ||--o{ order_status_history : "status_id"
    users ||--o{ order_status_history : "changed_by"
    orders ||--o| reviews : "order_id"
    users ||--o{ reviews : "user_id"
    users |o--o{ reviews : "validated_by"
```

### 2.3 Domaine Utilisateurs & Sécurité

Gère les comptes utilisateurs, l'authentification, les messages de contact, les horaires et la limitation de débit.

```mermaid
erDiagram
    users {
        CHAR(36) id PK
        VARCHAR(100) nom
        VARCHAR(100) prenom
        VARCHAR(255) email
        VARCHAR(255) password
        VARCHAR(20) gsm
        VARCHAR(255) adresse
        CHAR(5) code_postal
        VARCHAR(255) city
        ENUM_role role
        BOOLEAN actif
        DATETIME created_at
        DATETIME updated_at
    }

    password_reset_tokens {
        INT id PK
        CHAR(36) user_id FK
        VARCHAR(255) token_hash
        DATETIME expires_at
        BOOLEAN used
        DATETIME created_at
    }

    contacts {
        INT id PK
        VARCHAR(255) email
        VARCHAR(200) title
        TEXT message
        BOOLEAN processed
        CHAR(36) processed_by FK
        DATETIME processed_at
        DATETIME created_at
    }

    schedules {
        INT id PK
        ENUM_day day_of_week
        TIME opening_time
        TIME closing_time
        BOOLEAN closed
        DATETIME updated_at
        CHAR(36) updated_by FK
    }

    rate_limits {
        INT id PK
        VARCHAR(50) action
        VARCHAR(64) identifier
        INT attempts
        DATETIME first_attempt_at
        DATETIME blocked_until
    }

    users ||--o{ password_reset_tokens : "user_id"
    users |o--o{ contacts : "processed_by"
    users |o--o{ schedules : "updated_by"
```

---

## 3. Dictionnaire de données

### Table `users`

| Colonne | Type | Contrainte | Description |
| --- | --- | --- | --- |
| `id` | `CHAR(36)` | PK, DEFAULT UUID() | Identifiant UUID unique |
| `nom` | `VARCHAR(100)` | NOT NULL | Nom de famille |
| `prenom` | `VARCHAR(100)` | NOT NULL | Prénom |
| `email` | `VARCHAR(255)` | NOT NULL, UNIQUE | Adresse email (login) |
| `password` | `VARCHAR(255)` | NOT NULL | Mot de passe haché (bcrypt) |
| `gsm` | `VARCHAR(20)` | NULL | Numéro de téléphone |
| `adresse` | `VARCHAR(255)` | NULL | Adresse postale |
| `code_postal` | `CHAR(5)` | NULL | Code postal |
| `city` | `VARCHAR(255)` | NULL | Ville |
| `role` | `ENUM('user','employe','admin')` | DEFAULT 'user' | Rôle applicatif |
| `actif` | `BOOLEAN` | DEFAULT TRUE | Compte actif/désactivé |
| `created_at` | `DATETIME` | DEFAULT NOW() | Date de création |
| `updated_at` | `DATETIME` | ON UPDATE NOW() | Dernière modification |

### Table `menus`

| Colonne | Type | Contrainte | Description |
| --- | --- | --- | --- |
| `id` | `INT` | PK, AUTO_INCREMENT | Identifiant |
| `title` | `VARCHAR(200)` | NOT NULL | Intitulé du menu |
| `description` | `TEXT` | NULL | Description détaillée |
| `theme_id` | `INT` | FK → themes | Thème culinaire |
| `regime_id` | `INT` | FK → regimes | Régime alimentaire |
| `min_people` | `INT` | NOT NULL, DEFAULT 1 | Nombre minimum de personnes |
| `base_price` | `DECIMAL(10,2)` | NOT NULL | Prix de base par personne |
| `conditions` | `TEXT` | NULL | Conditions particulières |
| `stock` | `INT` | DEFAULT 0 | Nombre de menus disponibles |
| `active` | `BOOLEAN` | DEFAULT TRUE | Menu visible en catalogue |

### Table `orders`

| Colonne | Type | Contrainte | Description |
| --- | --- | --- | --- |
| `id` | `INT` | PK, AUTO_INCREMENT | Identifiant commande |
| `user_id` | `CHAR(36)` | FK → users | Client commandant |
| `menu_id` | `INT` | FK → menus | Menu commandé |
| `nb_people` | `INT` | NOT NULL | Nombre de personnes |
| `menu_price` | `DECIMAL(10,2)` | NOT NULL | Prix menu à l'instant T |
| `delivery_cost` | `DECIMAL(10,2)` | DEFAULT 0 | Frais de livraison |
| `total_price` | `DECIMAL(10,2)` | NOT NULL | Total TTC |
| `reduction` | `DECIMAL(10,2)` | DEFAULT 0 | Réduction appliquée |
| `delivery_address` | `TEXT` | NOT NULL | Adresse de livraison |
| `delivery_city` | `VARCHAR(100)` | NOT NULL | Ville de livraison |
| `delivery_date` | `DATE` | NOT NULL | Date de livraison souhaitée |
| `delivery_time` | `TIME` | NOT NULL | Heure de livraison souhaitée |
| `status_id` | `INT` | FK → order_status | Statut courant |
| `material_loaned` | `BOOLEAN` | DEFAULT FALSE | Matériel prêté au client |
| `material_returned` | `BOOLEAN` | DEFAULT FALSE | Matériel restitué |
| `material_return_deadline` | `DATETIME` | NULL | Date limite de retour matériel |
| `cancellation_reason` | `TEXT` | NULL | Motif d'annulation |
| `cancelled_by` | `CHAR(36)` | FK → users, NULL | Auteur de l'annulation |
| `contact_method` | `ENUM('email','telephone')` | NULL | Méthode de contact préférée |

### Table `reviews`

| Colonne | Type | Contrainte | Description |
| --- | --- | --- | --- |
| `id` | `INT` | PK, AUTO_INCREMENT | Identifiant |
| `order_id` | `INT` | FK → orders, UNIQUE | Commande évaluée (1 seul avis) |
| `user_id` | `CHAR(36)` | FK → users | Auteur de l'avis |
| `rating` | `TINYINT` | CHECK 1..5 | Note de 1 à 5 |
| `comment` | `TEXT` | NULL | Commentaire libre |
| `status` | `ENUM('pending','approved','rejected')` | DEFAULT 'pending' | État de modération |
| `validated_by` | `CHAR(36)` | FK → users, NULL | Modérateur (employé/admin) |
| `validated_at` | `DATETIME` | NULL | Date de modération |
| `reject_reason` | `TEXT` | NULL | Raison de rejet |

---

## 4. Conventions et notes

### Clés primaires

- `users.id` utilise `CHAR(36)` avec `DEFAULT (UUID())` — identifiant non prédictible pour la sécurité.
- Toutes les autres tables utilisent `INT AUTO_INCREMENT`.

### Horodatage

- `created_at` : positionné à `DEFAULT CURRENT_TIMESTAMP`, jamais mis à jour.
- `updated_at` : positionné à `ON UPDATE CURRENT_TIMESTAMP`, suit automatiquement chaque modification.

### Workflow des statuts commande

| Ordre | Statut | Description |
| --- | --- | --- |
| 1 | En attente | Reçue, en attente de validation |
| 2 | Acceptée | Validée par l'équipe |
| 3 | En préparation | En cours de préparation |
| 4 | En cours de livraison | Expédiée |
| 5 | Livrée | Reçue par le client |
| 6 | En attente du retour de matériel | Matériel prêté à récupérer |
| 7 | Terminée | Cycle complet |
| 8 | Annulée | Commande annulée |

Chaque changement de statut est archivé dans `order_status_history` avec l'auteur du changement.

### Référentiel allergènes

14 allergènes déclarés conformément à la réglementation européenne (INCO) :
Gluten, Crustacés, Œufs, Poissons, Arachides, Soja, Lactose, Fruits à coque, Céleri, Moutarde, Sésame, Sulfites, Lupin, Mollusques.

### Table technique `rate_limits`

Table de sécurité applicative (anti-brute-force) : non modélisée dans le MCD car elle n'appartient pas au domaine métier. Elle stocke les tentatives par `action` (ex. `login`) et `identifier` (IP ou email haché).

### Rôles utilisateurs

| Rôle | Accès |
| --- | --- |
| `user` | Catalogue, commandes propres, avis |
| `employe` | Gestion commandes, modération avis, plats et menus, horaires |
| `admin` | Accès complet + gestion des employés |
