-- Données de démonstration Vite & Gourmand
-- Exécuter : mysql -u root --default-character-set=utf8mb4 viteetgourmand < database/seed.sql

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE viteetgourmand;

INSERT INTO theme (id, label, slug) VALUES
(1, 'Noël', 'noel'),
(2, 'Pâques', 'paques'),
(3, 'Classique', 'classique'),
(4, 'Événement', 'evenement');

INSERT INTO diet (id, label, slug) VALUES
(1, 'Classique', 'classique'),
(2, 'Végétarien', 'vegetarien'),
(3, 'Vegan', 'vegan');

INSERT INTO allergen (id, label) VALUES
(1, 'Gluten'), (2, 'Lactose'), (3, 'Fruits à coque'), (4, 'Œuf'), (5, 'Poisson');

INSERT INTO user (id, email, roles, password, first_name, last_name, phone, address, postal_code, city, country, is_active, created_at) VALUES
(1, 'jose@vitegourmand.fr', '["ROLE_ADMIN"]', '$2b$13$h2QMfaLw9wk8R67hoPIsi.Uy9XqgNYyGtD4Viq6.bJN7tlTmuQ02q', 'José', 'Martin', '0601020304', '12 Quai des Chartrons', '33000', 'Bordeaux', 'France', 1, NOW()),
(2, 'employe@vitegourmand.fr', '["ROLE_EMPLOYEE"]', '$2b$13$6XYTowp69lVy5xmmdRPum.LcPjIhW.f5uwRhbZsy3dPYSeJhLEzLC', 'Julie', 'Dupont', '0605060708', '12 Quai des Chartrons', '33000', 'Bordeaux', 'France', 1, NOW()),
(3, 'client@example.com', '["ROLE_USER"]', '$2b$13$AOi4RnQM0bBnsTyIPKACgO2U1EF7LxgaSwpDqInQoK41vmqygYBvS', 'Alice', 'Durand', '0611223344', '8 Cours de l''Intendance', '33000', 'Bordeaux', 'France', 1, NOW());

INSERT INTO opening_hour (day_of_week, opening_time, closing_time, is_closed) VALUES
('LUNDI', '09:00:00', '18:00:00', 0),
('MARDI', '09:00:00', '18:00:00', 0),
('MERCREDI', '09:00:00', '18:00:00', 0),
('JEUDI', '09:00:00', '18:00:00', 0),
('VENDREDI', '09:00:00', '18:00:00', 0),
('SAMEDI', '09:00:00', '13:00:00', 0),
('DIMANCHE', NULL, NULL, 1);

INSERT INTO dish (id, title, description, type, is_active, created_at) VALUES
(1, 'Velouté de potiron', 'Velouté onctueux aux graines', 'ENTREE', 1, NOW()),
(2, 'Magret de canard', 'Magret rôti, sauce miel', 'PLAT', 1, NOW()),
(3, 'Bûche chocolat', 'Bûche maison chocolat noir', 'DESSERT', 1, NOW()),
(4, 'Salade de chèvre chaud', 'Salade verte, toasts de chèvre', 'ENTREE', 1, NOW()),
(5, 'Tournedos de bœuf', 'Pommes sarladaises', 'PLAT', 1, NOW()),
(6, 'Tarte aux pommes', 'Pâte maison, pommes caramélisées', 'DESSERT', 1, NOW());

INSERT INTO dish_allergen (dish_id, allergen_id) VALUES (1, 2), (2, 2), (3, 2), (3, 4), (4, 2), (5, 2);

INSERT INTO menu (id, title, slug, description, conditions_text, min_people, price_per_person, stock_quantity, is_active, created_at, theme_id, diet_id) VALUES
(1, 'Menu Festin de Noël', 'menu-festin-noel', 'Menu chaleureux pour célébrer Noël en famille.', 'Commande minimum 7 jours avant la prestation. Conservation au frais 48h.', 6, 42.00, 24, 1, NOW(), 1, 1),
(2, 'Menu Pâques Gourmand', 'menu-paques-gourmand', 'Menu printanier pour Pâques.', 'Commande minimum 5 jours avant. Allergènes indiqués par plat.', 4, 38.50, 8, 1, NOW(), 2, 1),
(3, 'Menu Classique Bordeaux', 'menu-classique-bordeaux', 'Menu traiteur polyvalent pour toutes occasions.', 'Livraison Bordeaux incluse. Hors Bordeaux : frais calculés à la commande.', 8, 35.00, 10, 1, NOW(), 3, 1);

INSERT INTO menu_dish (menu_id, dish_id) VALUES
(1, 1), (1, 2), (1, 3),
(2, 4), (2, 5), (2, 6),
(3, 1), (3, 5), (3, 6);
