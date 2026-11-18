-- Installation et benchmark complets en une seule commande.
-- Ce fichier est prévu pour le client MySQL en ligne de commande.
-- Depuis le dossier du projet :
-- mysql -u root -p < installer_benchmark_complet.sql
--
-- Dans phpMyAdmin, SOURCE n'est pas pris en charge. Utiliser alors
-- le bouton « Charge optimisée » de l'application, puis importer
-- seulement benchmark_requetes_principales.sql.

DROP DATABASE IF EXISTS velo_libre_service;

SOURCE velo_libre_service_mysql.sql;
SOURCE donnees_test.sql;
SOURCE donnees_charge_optimisation.sql;
SOURCE donnees_benchmark_complet.sql;
SOURCE benchmark_requetes_principales.sql;
