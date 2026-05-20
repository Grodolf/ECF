-- Projet : ECF 
-- Données de test : Vite & Gourmand
-- SGBD : MariaDB
-- Auteur : DERVAUX Rodolphe
-- 
USE vite_et_gourmand;
-- =====
-- PLATS
-- =====
-- Entrées
INSERT INTO dishes (name, description, dish_type_id, active)
VALUES (
        'Foie gras de canard mi-cuit',
        'Foie gras maison avec gelée au porto et pain d''épices',
        1,
        TRUE
    ),
    (
        'Velouté de châtaignes',
        'Crème de châtaignes aux cèpes et croutons',
        1,
        TRUE
    ),
    (
        'Saumon fumé maison',
        'Saumon fumé à froid, crème d''aneth et blinis',
        1,
        TRUE
    ),
    (
        'Salade de chèvre chaud',
        'Mesclun, crottin de chèvre grillé, noix et miel',
        1,
        TRUE
    ),
    (
        'Carpaccio de Saint-Jacques',
        'Noix de Saint-Jacques crues, huile de truffe et agrumes',
        1,
        TRUE
    ),
    (
        'Tartare de légumes',
        'Légumes crus assaisonnés, huile d''olive et basilic',
        1,
        TRUE
    );
-- Plats principaux
INSERT INTO dishes (name, description, dish_type_id, active)
VALUES (
        'Chapon fermier aux morilles',
        'Chapon rôti, sauce aux morilles et légumes de saison',
        2,
        TRUE
    ),
    (
        'Pavé de bœuf Rossini',
        'Filet de bœuf, foie gras poêlé et sauce périgueux',
        2,
        TRUE
    ),
    (
        'Bar en croûte de sel',
        'Bar de ligne cuit en croûte de sel, beurre blanc',
        2,
        TRUE
    ),
    (
        'Gigot d''agneau aux herbes',
        'Gigot rôti aux herbes de Provence, gratin dauphinois',
        2,
        TRUE
    ),
    (
        'Risotto aux truffes',
        'Risotto crémeux aux copeaux de truffe noire',
        2,
        TRUE
    ),
    (
        'Tajine de légumes',
        'Légumes mijotés aux épices orientales, semoule',
        2,
        TRUE
    );
-- Desserts
INSERT INTO dishes (name, description, dish_type_id, active)
VALUES (
        'Bûche de Noël chocolat',
        'Biscuit chocolat, mousse pralinée et glaçage brillant',
        3,
        TRUE
    ),
    (
        'Tarte au citron meringuée',
        'Pâte sablée, crème citron et meringue italienne',
        3,
        TRUE
    ),
    (
        'Tiramisu maison',
        'Mascarpone, café et cacao amer',
        3,
        TRUE
    ),
    (
        'Fondant au chocolat',
        'Cœur coulant au chocolat noir 70%',
        3,
        TRUE
    ),
    (
        'Crème brûlée vanille',
        'Crème onctueuse à la vanille de Madagascar',
        3,
        TRUE
    ),
    (
        'Salade de fruits exotiques',
        'Fruits frais de saison, coulis de mangue',
        3,
        TRUE
    );
-- =============================
-- ASSOCIATIONS PLATS-ALLERGÈNES
-- =============================
-- Foie gras : Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (1, 7);
-- Velouté châtaignes : Lait, Céleri
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (2, 7),
    (2, 9);
-- Saumon fumé : Poissons, Gluten (blinis)
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (3, 1),
    (3, 4);
-- Salade chèvre : Lait, Gluten, Fruits à coque
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (4, 1),
    (4, 7),
    (4, 8);
-- Carpaccio Saint-Jacques : Mollusques
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (5, 14);
-- Chapon : Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (7, 7);
-- Bœuf Rossini : Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (8, 7);
-- Bar : Poissons
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (9, 4);
-- Gigot : Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (10, 7);
-- Risotto : Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (11, 7);
-- Bûche : Gluten, Œufs, Lait, Fruits à coque
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (13, 1),
    (13, 3),
    (13, 7),
    (13, 8);
-- Tarte citron : Gluten, Œufs, Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (14, 1),
    (14, 3),
    (14, 7);
-- Tiramisu : Gluten, Œufs, Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (15, 1),
    (15, 3),
    (15, 7);
-- Fondant chocolat : Gluten, Œufs, Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (16, 1),
    (16, 3),
    (16, 7);
-- Crème brûlée : Œufs, Lait
INSERT INTO dish_allergenes (dish_id, allergene_id)
VALUES (17, 3),
    (17, 7);
-- =====
-- MENUS
-- =====
-- Menu de Noël - Classique
INSERT INTO menus (
        title,
        description,
        theme_id,
        regime_id,
        min_people,
        base_price,
        conditions,
        stock,
        active
    )
VALUES (
        'Menu Tradition de Noël',
        'Un menu festif qui ravira vos convives avec des produits nobles et une cuisine raffinée',
        1,
        1,
        6,
        18.00,
        'Le menu sera livré dans des contenants isotherme à restituer.',
        10,
        TRUE
    );
-- Menu de Noël - Végétarien
INSERT INTO menus (
        title,
        description,
        theme_id,
        regime_id,
        min_people,
        base_price,
        conditions,
        stock,
        active
    )
VALUES (
        'Menu Végétarien des Fêtes',
        'Un menu végétarien raffiné pour célébrer les fêtes autrement',
        1,
        2,
        4,
        15.00,
        '',
        8,
        TRUE
    );
-- Menu Pâques - Classique
INSERT INTO menus (
        title,
        description,
        theme_id,
        regime_id,
        min_people,
        base_price,
        conditions,
        stock,
        active
    )
VALUES (
        'Menu Printanier de Pâques',
        'Un menu frais et léger pour célébrer le printemps',
        2,
        1,
        8,
        11.50,
        'Peut varier en fonction des disponibilités',
        15,
        TRUE
    );
-- Menu Classique
INSERT INTO menus (
        title,
        description,
        theme_id,
        regime_id,
        min_people,
        base_price,
        conditions,
        stock,
        active
    )
VALUES (
        'Menu Prestige',
        'Notre menu signature pour vos événements d''exception',
        3,
        1,
        10,
        12.00,
        'Service sur place possible avec supplément.',
        5,
        TRUE
    );
-- Menu Été
INSERT INTO menus (
        title,
        description,
        theme_id,
        regime_id,
        min_people,
        base_price,
        conditions,
        stock,
        active
    )
VALUES (
        'Menu Fraîcheur d''Été',
        'Un menu léger et rafraîchissant pour vos événements estivaux',
        5,
        1,
        6,
        10.80,
        'Nécessite une cuisine équipée sur place.',
        12,
        TRUE
    );
-- Menu Vegan
INSERT INTO menus (
        title,
        description,
        theme_id,
        regime_id,
        min_people,
        base_price,
        conditions,
        stock,
        active
    )
VALUES (
        'Menu 100% Végétal',
        'Un menu vegan gourmand et coloré',
        3,
        3,
        4,
        13.50,
        '',
        10,
        TRUE
    );
-- ========================
-- ASSOCIATIONS MENUS-PLATS
-- ========================
-- Menu Tradition de Noël (1) : Foie gras + Chapon + Bûche
INSERT INTO menu_dishes (menu_id, dish_id)
VALUES (1, 1),
    (1, 7),
    (1, 13);
-- Menu Végétarien Fêtes (2) : Tartare légumes + Risotto truffes + Crème brûlée
INSERT INTO menu_dishes (menu_id, dish_id)
VALUES (2, 6),
    (2, 11),
    (2, 17);
-- Menu Printanier Pâques (3) : Carpaccio SJ + Gigot + Tarte citron
INSERT INTO menu_dishes (menu_id, dish_id)
VALUES (3, 5),
    (3, 10),
    (3, 14);
-- Menu Prestige (4) : Saumon fumé + Bœuf Rossini + Fondant chocolat
INSERT INTO menu_dishes (menu_id, dish_id)
VALUES (4, 3),
    (4, 8),
    (4, 16);
-- Menu Été (5) : Salade chèvre + Bar en croûte + Salade fruits
INSERT INTO menu_dishes (menu_id, dish_id)
VALUES (5, 4),
    (5, 9),
    (5, 18);
-- Menu Vegan (6) : Tartare légumes + Tajine légumes + Salade fruits
INSERT INTO menu_dishes (menu_id, dish_id)
VALUES (6, 6),
    (6, 12),
    (6, 18);
-- =====================================================
-- FIN DES DONNÉES DE TEST
-- =====================================================
