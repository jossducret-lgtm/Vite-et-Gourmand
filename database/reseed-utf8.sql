-- Vide les tables de démo avant réimport UTF-8
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
USE viteetgourmand;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE menu_dish;
TRUNCATE TABLE dish_allergen;
TRUNCATE TABLE menu_image;
TRUNCATE TABLE review;
TRUNCATE TABLE order_status_history;
TRUNCATE TABLE menu_order;
TRUNCATE TABLE menu;
TRUNCATE TABLE dish;
TRUNCATE TABLE allergen;
TRUNCATE TABLE theme;
TRUNCATE TABLE diet;
TRUNCATE TABLE opening_hour;
TRUNCATE TABLE user;
SET FOREIGN_KEY_CHECKS = 1;
