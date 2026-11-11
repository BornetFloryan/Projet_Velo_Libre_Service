-- Projet BD70 - Jeu de données de démonstration
-- À importer APRÈS velo_libre_service_mysql.sql dans une base vide.
-- Les mots de passe de démonstration correspondent à : password

USE velo_libre_service;

-- ============================================================
-- CATEGORIES ET FORMULES
-- ============================================================

INSERT INTO categorie_velo (libelle) VALUES
    ('mecanique'),
    ('electrique');

INSERT INTO formule (
    nom,
    prix_base,
    duree_gratuite_minutes,
    tarif_par_tranche,
    supplement_electrique
) VALUES
    ('Ticket journée', 1.00, 15, 0.50, 0.50),
    ('Abonnement mensuel', 0.00, 30, 0.40, 0.30),
    ('Abonnement annuel réduit', 0.00, 45, 0.25, 0.20);

-- ============================================================
-- STATIONS ET BORNES
-- ============================================================

INSERT INTO station (
    nom, adresse, latitude, longitude, capacite_bornes,
    etat, seuil_velos_min, seuil_bornes_min
) VALUES
    ('Gare centrale', 'Place de la Gare, Belfort', 47.637300, 6.863900, 8, 'en_service', 2, 2),
    ('UTBM Montbéliard', 'Rue Thierry Mieg, Montbéliard', 47.511700, 6.798300, 8, 'en_service', 2, 2),
    ('Centre-ville', 'Place d''Armes, Belfort', 47.639600, 6.862600, 8, 'en_service', 2, 2),
    ('Techn''hom', 'Rue de l''As de Carreau, Belfort', 47.651900, 6.841600, 8, 'en_travaux', 1, 2);

-- Gare centrale (1 à 8)
INSERT INTO borne (id_borne, etat, id_station) VALUES
    (1, 'disponible', 1), (2, 'disponible', 1),
    (3, 'disponible', 1), (4, 'disponible', 1),
    (5, 'disponible', 1), (6, 'disponible', 1),
    (7, 'disponible', 1), (8, 'disponible', 1);

-- UTBM Montbéliard (9 à 16)
INSERT INTO borne (id_borne, etat, id_station) VALUES
    (9, 'disponible', 2), (10, 'disponible', 2),
    (11, 'disponible', 2), (12, 'disponible', 2),
    (13, 'disponible', 2), (14, 'disponible', 2),
    (15, 'disponible', 2), (16, 'disponible', 2);

-- Centre-ville (17 à 24)
INSERT INTO borne (id_borne, etat, id_station) VALUES
    (17, 'disponible', 3), (18, 'disponible', 3),
    (19, 'disponible', 3), (20, 'disponible', 3),
    (21, 'disponible', 3), (22, 'disponible', 3),
    (23, 'disponible', 3), (24, 'disponible', 3);

-- Techn'hom (25 à 32)
INSERT INTO borne (id_borne, etat, id_station) VALUES
    (25, 'disponible', 4), (26, 'disponible', 4),
    (27, 'maintenance', 4), (28, 'disponible', 4),
    (29, 'disponible', 4), (30, 'disponible', 4),
    (31, 'disponible', 4), (32, 'disponible', 4);

-- ============================================================
-- COMPTES, ABONNEMENTS ET PORTEFEUILLES
-- ============================================================

INSERT INTO usager (
    nom, prenom, email, mot_de_passe_hash, solde_portefeuille, tarif_reduit
) VALUES
    ('Martin', 'Alice', 'alice.martin@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 50.00, FALSE),
    ('Bernard', 'Lucas', 'lucas.bernard@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 25.00, FALSE),
    ('Petit', 'Lina', 'lina.petit@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 35.00, TRUE),
    ('Durand', 'Hugo', 'hugo.durand@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 15.00, FALSE);

INSERT INTO operateur (
    nom_prenom, email, mot_de_passe_hash, role_operateur
) VALUES
    ('Camille Leroy', 'camille.leroy@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'maintenance'),
    ('Nolan Robert', 'nolan.robert@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'regulation'),
    ('Sarah Thomas', 'sarah.thomas@example.test', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'administration');

INSERT INTO abonnement (date_debut, date_fin, id_usager, id_formule) VALUES
    (CURDATE() - INTERVAL 180 DAY, CURDATE() + INTERVAL 185 DAY, 1, 2),
    (CURDATE() - INTERVAL 20 DAY, CURDATE() + INTERVAL 345 DAY, 2, 1),
    (CURDATE() - INTERVAL 90 DAY, CURDATE() + INTERVAL 275 DAY, 3, 3),
    (CURDATE() - INTERVAL 10 DAY, CURDATE() + INTERVAL 20 DAY, 4, 1);

INSERT INTO operation_portefeuille (nature, montant, id_usager) VALUES
    ('rechargement', 50.00, 1),
    ('rechargement', 25.00, 2),
    ('rechargement', 35.00, 3),
    ('rechargement', 15.00, 4);

-- ============================================================
-- FLOTTE
-- Les triggers créent automatiquement les historiques initiaux.
-- ============================================================

INSERT INTO velo (
    numero_serie, etat, niveau_batterie, date_mise_service, id_categorie, id_borne_actuelle
) VALUES
    ('VELO-001', 'disponible', NULL, CURDATE() - INTERVAL 500 DAY, 1, 1),
    ('VELO-002', 'disponible', 92, CURDATE() - INTERVAL 300 DAY, 2, 2),
    ('VELO-003', 'disponible', NULL, CURDATE() - INTERVAL 700 DAY, 1, 10),
    ('VELO-004', 'disponible', 68, CURDATE() - INTERVAL 250 DAY, 2, 11),
    ('VELO-005', 'maintenance', 20, CURDATE() - INTERVAL 900 DAY, 2, 17),
    ('VELO-006', 'atelier', NULL, CURDATE() - INTERVAL 1100 DAY, 1, NULL),
    ('VELO-007', 'disponible', NULL, CURDATE() - INTERVAL 400 DAY, 1, 18),
    ('VELO-008', 'hors_service', 5, CURDATE() - INTERVAL 800 DAY, 2, 25),
    ('VELO-009', 'disponible', 76, CURDATE() - INTERVAL 100 DAY, 2, 26);

-- ============================================================
-- TRAJET HISTORIQUE PAYE
-- Insertion puis restitution : les triggers calculent durée, coût et paiement.
-- ============================================================

INSERT INTO trajet (
    date_depart, id_usager, numero_serie, id_station_depart, id_borne_depart
) VALUES (
    NOW() - INTERVAL 21 DAY,
    1,
    'VELO-001',
    1,
    1
);

SET @trajet_historique := LAST_INSERT_ID();

UPDATE trajet
SET date_arrivee = DATE_ADD(date_depart, INTERVAL 42 MINUTE),
    id_station_arrivee = 2,
    id_borne_arrivee = 12,
    note_evaluation = 5
WHERE id_trajet = @trajet_historique;

-- ============================================================
-- RESERVATION PUIS LOCATION EN COURS
-- ============================================================

INSERT INTO reservation (date_reservation, statut, id_usager, numero_serie) VALUES
    (NOW() - INTERVAL 5 MINUTE, 'active', 2, 'VELO-004');

INSERT INTO trajet (
    date_depart, id_usager, numero_serie, id_station_depart, id_borne_depart
) VALUES (
    NOW() - INTERVAL 3 MINUTE,
    2,
    'VELO-004',
    2,
    11
);

-- ============================================================
-- SIGNALEMENT, INTERVENTION ET REGULATION
-- ============================================================

INSERT INTO signalement (
    date_detection, description, id_usager, numero_serie, id_station
) VALUES (
    NOW() - INTERVAL 2 DAY,
    'Batterie défaillante et frein arrière peu réactif.',
    3,
    'VELO-005',
    3
);

SET @signalement_maintenance := LAST_INSERT_ID();

INSERT INTO intervention (
    date_debut, date_fin, type_action, id_signalement, id_operateur
) VALUES (
    NOW() - INTERVAL 1 DAY,
    NULL,
    'Diagnostic batterie et réparation du frein',
    @signalement_maintenance,
    1
);

INSERT INTO tournee_regulation (
    date_tournee, immatriculation_vehicule, id_operateur
) VALUES (
    CURDATE(),
    'AB-123-CD',
    2
);

SET @tournee_regulation := LAST_INSERT_ID();

INSERT INTO mouvement_regulation (
    date_heure, nature, id_tournee, numero_serie, id_station
) VALUES
    (NOW() - INTERVAL 90 MINUTE, 'retrait', @tournee_regulation, 'VELO-007', 3),
    (NOW() - INTERVAL 45 MINUTE, 'depot', @tournee_regulation, 'VELO-007', 1);

-- Position finale du vélo déplacé : le trigger crée l'historique correspondant.
UPDATE velo
SET id_borne_actuelle = 5
WHERE numero_serie = 'VELO-007';

-- Requêtes de vérification conseillées après import :
-- SELECT * FROM v_etat_station;
-- SELECT * FROM v_statistiques_journalieres;
-- SELECT * FROM v_recettes_par_formule;
-- SELECT * FROM historique_etat ORDER BY date_heure DESC;
-- SELECT * FROM historique_position ORDER BY date_heure DESC;
