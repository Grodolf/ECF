-- Projet : ECF
-- Base de données : vite_et_gourmand
-- SGBD : MariaDB
-- Auteur : DERVAUX Rodolphe
--
-- Suppression de la base si elle existe et création
-- DROP DATABASE IF EXISTS vite_et_gourmand;
CREATE DATABASE IF NOT EXISTS vite_et_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vite_et_gourmand;
-- 
-- TABLE : users
-- 
CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    gsm VARCHAR(20),
    adresse VARCHAR(255),
    code_postal CHAR(5),
    city VARCHAR(255),
    role ENUM('user', 'employe', 'admin') DEFAULT 'user',
    actif BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE = InnoDB;
-- 
-- TABLE : password_reset_tokens
-- 
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token_hash),
    INDEX idx_expires (expires_at)
) ENGINE = InnoDB;
-- 
-- TABLE : contacts
-- 
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    processed_by CHAR(36),
    processed_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX idx_processed (processed),
    INDEX idx_created (created_at)
) ENGINE = InnoDB;
-- 
-- TABLE : themes
-- 
CREATE TABLE IF NOT EXISTS themes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
-- 
-- TABLE : regimes
-- 
CREATE TABLE IF NOT EXISTS regimes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
-- 
-- TABLE : allergenes
-- 
CREATE TABLE IF NOT EXISTS allergenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
-- 
-- TABLE : dish_types
-- 
CREATE TABLE IF NOT EXISTS dish_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
-- 
-- TABLE : dishes
-- 
CREATE TABLE IF NOT EXISTS dishes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    dish_type_id INT NOT NULL,
    image_url VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dish_type_id) REFERENCES dish_types(id),
    INDEX idx_dish_type (dish_type_id),
    INDEX idx_active (active)
) ENGINE = InnoDB;
-- 
-- TABLE : dish_allergenes
-- 
CREATE TABLE IF NOT EXISTS dish_allergenes (
    dish_id INT NOT NULL,
    allergene_id INT NOT NULL,
    PRIMARY KEY (dish_id, allergene_id),
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE,
    FOREIGN KEY (allergene_id) REFERENCES allergenes(id) ON DELETE CASCADE
) ENGINE = InnoDB;
-- 
-- TABLE : menus
-- 
CREATE TABLE IF NOT EXISTS menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    theme_id INT NOT NULL,
    regime_id INT NOT NULL,
    min_people INT NOT NULL DEFAULT 1,
    base_price DECIMAL(10, 2) NOT NULL,
    conditions TEXT,
    stock INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theme_id) REFERENCES themes(id),
    FOREIGN KEY (regime_id) REFERENCES regimes(id),
    INDEX idx_theme (theme_id),
    INDEX idx_regime (regime_id),
    INDEX idx_active (active),
    INDEX idx_price (base_price)
) ENGINE = InnoDB;
-- 
-- TABLE : menu_dishes
-- 
CREATE TABLE IF NOT EXISTS menu_dishes (
    menu_id INT NOT NULL,
    dish_id INT NOT NULL,
    PRIMARY KEY (menu_id, dish_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
) ENGINE = InnoDB;
-- 
-- TABLE : menu_images
-- 
CREATE TABLE IF NOT EXISTS menu_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    display_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    INDEX idx_menu (menu_id)
) ENGINE = InnoDB;
-- 
-- TABLE : order_status
-- 
CREATE TABLE IF NOT EXISTS order_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    workflow_order INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;
-- 
-- TABLE : orders
-- 
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    menu_id INT NOT NULL,
    nb_people INT NOT NULL,
    menu_price DECIMAL(10, 2) NOT NULL,
    delivery_cost DECIMAL(10, 2) DEFAULT 0,
    total_price DECIMAL(10, 2) NOT NULL,
    reduction DECIMAL(10, 2) DEFAULT 0,
    -- Informations de livraison
    delivery_address TEXT NOT NULL,
    delivery_city VARCHAR(100) NOT NULL,
    delivery_date DATE NOT NULL,
    delivery_time TIME NOT NULL,
    -- Suivi
    status_id INT NOT NULL,
    material_loaned BOOLEAN DEFAULT FALSE,
    material_returned BOOLEAN DEFAULT FALSE,
    material_return_deadline DATETIME DEFAULT NULL,
    -- Annulation
    cancellation_reason TEXT NULL,
    cancelled_by CHAR(36) NULL,
    contact_method ENUM('email', 'telephone'),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (menu_id) REFERENCES menus(id),
    FOREIGN KEY (status_id) REFERENCES order_status(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id),
    INDEX idx_user (user_id),
    INDEX idx_status (status_id),
    INDEX idx_delivery_date (delivery_date),
    INDEX idx_created (created_at)
) ENGINE = InnoDB;
-- 
-- TABLE : order_status_history
-- 
CREATE TABLE IF NOT EXISTS order_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status_id INT NOT NULL,
    changed_by CHAR(36) NOT NULL,
    comment TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES order_status(id),
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX idx_order (order_id),
    INDEX idx_changed_at (changed_at)
) ENGINE = InnoDB;
-- 
-- TABLE : reviews
-- 
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE,
    user_id CHAR(36) NOT NULL,
    rating TINYINT NOT NULL CHECK (
        rating >= 1
        AND rating <= 5
    ),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    validated_by CHAR(36),
    validated_at DATETIME,
    reject_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (validated_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_order (order_id),
    INDEX idx_rating (rating)
) ENGINE = InnoDB;
-- 
-- TABLE : schedules
-- 
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM(
        'lundi',
        'mardi',
        'mercredi',
        'jeudi',
        'vendredi',
        'samedi',
        'dimanche'
    ) NOT NULL UNIQUE,
    opening_time TIME,
    closing_time TIME,
    closed BOOLEAN DEFAULT FALSE,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by CHAR(36),
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE = InnoDB;
--
-- TABLE : rate_limits
--
CREATE TABLE IF NOT EXISTS rate_limits (
id INT AUTO_INCREMENT PRIMARY KEY,
action VARCHAR(50) NOT NULL,
identifier VARCHAR(64) NOT NULL,
attempts INT NOT NULL DEFAULT 1,
first_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
blocked_until DATETIME DEFAULT NULL,
INDEX idx_action_identifier (action, identifier),
INDEX idx_blocked_until (blocked_until)
) ENGINE = InnoDB;
--
--
-- Données de départ
-- 
-- Themes
INSERT INTO themes (name, description)
VALUES (
        'Noël',
        'Menus festifs pour les fêtes de fin d''année'
    ),
    ('Pâques', 'Menus de printemps pour Pâques'),
    (
        'Tradition',
        'Menus traditionnels pour toute occasion'
    ),
    ('Événement', 'Menus pour événements spéciaux'),
    ('Été', 'Menus légers et rafraîchissants');
-- Régimes
INSERT INTO regimes (name, description)
VALUES (
        'Classique',
        'Menu sans restriction alimentaire particulière'
    ),
    ('Végétarien', 'Sans viande ni poisson'),
    ('Vegan', 'Sans aucun produit d''origine animale'),
    (
        'Sans gluten',
        'Pour les personnes intolérantes au gluten'
    ),
    (
        'Sans lactose',
        'Pour les personnes intolérantes au lactose'
    );
-- Allergènes
INSERT INTO allergenes (name, description)
VALUES ('Gluten', 'Céréales contenant du gluten'),
    (
        'Crustacés',
        'Crustacés et produits à base de crustacés'
    ),
    ('Œufs', 'Œufs et produits à base d''œufs'),
    (
        'Poissons',
        'Poissons et produits à base de poissons'
    ),
    (
        'Arachides',
        'Arachides et produits à base d''arachides'
    ),
    ('Soja', 'Soja et produits à base de soja'),
    ('Lactose', 'Lait et produits à base de lait'),
    (
        'Fruits à coque',
        'Amandes, noisettes, noix, etc.'
    ),
    ('Céleri', 'Céleri et produits à base de céleri'),
    (
        'Moutarde',
        'Moutarde et produits à base de moutarde'
    ),
    (
        'Sésame',
        'Graines de sésame et produits à base de sésame'
    ),
    ('Sulfites', 'Anhydride sulfureux et sulfites'),
    ('Lupin', 'Lupin et produits à base de lupin'),
    (
        'Mollusques',
        'Mollusques et produits à base de mollusques'
    );
-- Types de plats
INSERT INTO dish_types (name, display_order)
VALUES ('Entrée', 10),
    ('Plat principal', 20),
    ('Dessert', 30);
-- Status
INSERT INTO order_status (name, description, workflow_order)
VALUES (
        'En attente',
        'Commande reçue, en attente de validation',
        1
    ),
    ('Acceptée', 'Commande validée par l''équipe', 2),
    (
        'En préparation',
        'Commande en cours de préparation',
        3
    ),
    (
        'En cours de livraison',
        'Commande en cours de livraison',
        4
    ),
    ('Livrée', 'Commande livrée au client', 5),
    (
        'En attente du retour de matériel',
        'Matériel prêté, en attente de restitution',
        6
    ),
    ('Terminée', 'Commande complètement terminée', 7),
    ('Annulée', 'Commande annulée', 8);
-- Horaires
INSERT INTO schedules (day_of_week, opening_time, closing_time, closed)
VALUES ('lundi', NULL, NULL, TRUE),
    ('mardi', '09:00:00', '18:00:00', FALSE),
    ('mercredi', '09:00:00', '18:00:00', FALSE),
    ('jeudi', '09:00:00', '18:00:00', FALSE),
    ('vendredi', '09:00:00', '18:00:00', FALSE),
    ('samedi', '09:00:00', '18:00:00', FALSE),
    ('dimanche', '09:00:00', '12:00:00', FALSE);
-- Compte administrateur
-- Mot de passe : Admin123! (haché avec bcrypt)
INSERT INTO users (nom, prenom, email, password, role)
VALUES (
        'Moulin',
        'José',
        'admin@vite-et-gourmand.fr',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin'
    );
-- 
-- FIN
