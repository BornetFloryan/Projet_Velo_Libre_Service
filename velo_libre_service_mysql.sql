-- Projet BD70 - Vélo libre-service
-- Cible : MySQL 8.0+ (et non PostgreSQL)
-- Ce script crée le schéma, les contraintes, les index, les triggers et un événement planifié.

CREATE DATABASE IF NOT EXISTS velo_libre_service
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE velo_libre_service;

-- ============================================================
-- REFERENTIEL
-- ============================================================

CREATE TABLE IF NOT EXISTS categorie_velo (
    id_categorie INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL,
    CONSTRAINT uq_categorie_velo_libelle UNIQUE (libelle),
    CONSTRAINT ck_categorie_velo_libelle
        CHECK (libelle IN ('mecanique', 'electrique'))
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS formule (
    id_formule INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(80) NOT NULL,
    prix_base DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    duree_gratuite_minutes INT UNSIGNED NOT NULL DEFAULT 0,
    tarif_par_tranche DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    supplement_electrique DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT uq_formule_nom UNIQUE (nom),
    CONSTRAINT ck_formule_prix_base CHECK (prix_base >= 0),
    CONSTRAINT ck_formule_tarif CHECK (tarif_par_tranche >= 0),
    CONSTRAINT ck_formule_supplement CHECK (supplement_electrique >= 0)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS station (
    id_station INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL,
    capacite_bornes INT UNSIGNED NOT NULL,
    etat ENUM('en_service', 'hors_service', 'en_travaux') NOT NULL DEFAULT 'en_service',
    seuil_velos_min INT UNSIGNED NOT NULL DEFAULT 1,
    seuil_bornes_min INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT uq_station_nom UNIQUE (nom),
    CONSTRAINT ck_station_capacite CHECK (capacite_bornes > 0),
    CONSTRAINT ck_station_latitude CHECK (latitude BETWEEN -90 AND 90),
    CONSTRAINT ck_station_longitude CHECK (longitude BETWEEN -180 AND 180),
    CONSTRAINT ck_station_seuil_velos CHECK (seuil_velos_min <= capacite_bornes),
    CONSTRAINT ck_station_seuil_bornes CHECK (seuil_bornes_min <= capacite_bornes)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS borne (
    id_borne INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etat ENUM('disponible', 'hors_service', 'maintenance') NOT NULL DEFAULT 'disponible',
    id_station INT UNSIGNED NOT NULL,
    CONSTRAINT fk_borne_station
        FOREIGN KEY (id_station) REFERENCES station(id_station),
    INDEX idx_borne_station_etat (id_station, etat)
) ENGINE = InnoDB;

-- ============================================================
-- UTILISATEURS ET ABONNEMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS usager (
    id_usager INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(80) NOT NULL,
    prenom VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    solde_portefeuille DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tarif_reduit BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT uq_usager_email UNIQUE (email),
    CONSTRAINT ck_usager_solde CHECK (solde_portefeuille >= 0)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS abonnement (
    id_abonnement INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    id_usager INT UNSIGNED NOT NULL,
    id_formule INT UNSIGNED NOT NULL,
    CONSTRAINT fk_abonnement_usager
        FOREIGN KEY (id_usager) REFERENCES usager(id_usager),
    CONSTRAINT fk_abonnement_formule
        FOREIGN KEY (id_formule) REFERENCES formule(id_formule),
    CONSTRAINT ck_abonnement_dates CHECK (date_fin >= date_debut),
    INDEX idx_abonnement_usager_periode (id_usager, date_debut, date_fin)
) ENGINE = InnoDB;

-- ============================================================
-- FLOTTE, RESERVATIONS ET TRAJETS
-- ============================================================

CREATE TABLE IF NOT EXISTS velo (
    numero_serie VARCHAR(50) PRIMARY KEY,
    etat ENUM('disponible', 'reserve', 'en_cours', 'maintenance', 'atelier', 'hors_service')
        NOT NULL DEFAULT 'disponible',
    niveau_batterie INT UNSIGNED NULL,
    date_mise_service DATE NOT NULL,
    id_categorie INT UNSIGNED NOT NULL,
    id_borne_actuelle INT UNSIGNED NULL,
    CONSTRAINT fk_velo_categorie
        FOREIGN KEY (id_categorie) REFERENCES categorie_velo(id_categorie),
    CONSTRAINT fk_velo_borne
        FOREIGN KEY (id_borne_actuelle) REFERENCES borne(id_borne),
    CONSTRAINT uq_velo_borne_actuelle UNIQUE (id_borne_actuelle),
    CONSTRAINT ck_velo_batterie CHECK (niveau_batterie IS NULL OR niveau_batterie BETWEEN 0 AND 100),
    INDEX idx_velo_etat_categorie (etat, id_categorie)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS reservation (
    id_reservation INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('active', 'utilisee', 'expiree', 'annulee') NOT NULL DEFAULT 'active',
    id_usager INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    CONSTRAINT fk_reservation_usager
        FOREIGN KEY (id_usager) REFERENCES usager(id_usager),
    CONSTRAINT fk_reservation_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    INDEX idx_reservation_expiration (statut, date_reservation),
    INDEX idx_reservation_velo_active (numero_serie, statut)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS trajet (
    id_trajet INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_depart DATETIME NOT NULL,
    date_arrivee DATETIME NULL,
    cout_facture DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duree_minutes INT UNSIGNED NULL,
    note_evaluation INT UNSIGNED NULL,
    statut_paiement ENUM('en_attente', 'payee', 'annulee') NOT NULL DEFAULT 'en_attente',
    date_paiement DATETIME NULL,
    id_usager INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    id_station_depart INT UNSIGNED NOT NULL,
    id_station_arrivee INT UNSIGNED NULL,
    id_borne_depart INT UNSIGNED NOT NULL,
    id_borne_arrivee INT UNSIGNED NULL,
    CONSTRAINT fk_trajet_usager
        FOREIGN KEY (id_usager) REFERENCES usager(id_usager),
    CONSTRAINT fk_trajet_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    CONSTRAINT fk_trajet_station_depart
        FOREIGN KEY (id_station_depart) REFERENCES station(id_station),
    CONSTRAINT fk_trajet_station_arrivee
        FOREIGN KEY (id_station_arrivee) REFERENCES station(id_station),
    CONSTRAINT fk_trajet_borne_depart
        FOREIGN KEY (id_borne_depart) REFERENCES borne(id_borne),
    CONSTRAINT fk_trajet_borne_arrivee
        FOREIGN KEY (id_borne_arrivee) REFERENCES borne(id_borne),
    CONSTRAINT ck_trajet_dates
        CHECK (date_arrivee IS NULL OR date_arrivee >= date_depart),
    CONSTRAINT ck_trajet_cout CHECK (cout_facture >= 0),
    CONSTRAINT ck_trajet_note CHECK (note_evaluation IS NULL OR note_evaluation BETWEEN 1 AND 5),
    INDEX idx_trajet_usager_date (id_usager, date_depart),
    INDEX idx_trajet_velo_ouvert (numero_serie, date_arrivee),
    INDEX idx_trajet_statut_paiement (statut_paiement, date_depart)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS operation_portefeuille (
    id_operation INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nature ENUM('rechargement', 'paiement_trajet', 'remboursement') NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    id_usager INT UNSIGNED NOT NULL,
    id_trajet INT UNSIGNED NULL,
    CONSTRAINT fk_operation_usager
        FOREIGN KEY (id_usager) REFERENCES usager(id_usager),
    CONSTRAINT fk_operation_trajet
        FOREIGN KEY (id_trajet) REFERENCES trajet(id_trajet),
    CONSTRAINT uq_operation_paiement_trajet UNIQUE (id_trajet),
    CONSTRAINT ck_operation_montant CHECK (montant <> 0),
    INDEX idx_operation_usager_date (id_usager, date_heure)
) ENGINE = InnoDB;

-- ============================================================
-- SIGNALEMENTS, MAINTENANCE ET REGULATION
-- ============================================================

CREATE TABLE IF NOT EXISTS signalement (
    id_signalement INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_detection DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    description TEXT NOT NULL,
    id_usager INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    id_station INT UNSIGNED NOT NULL,
    CONSTRAINT fk_signalement_usager
        FOREIGN KEY (id_usager) REFERENCES usager(id_usager),
    CONSTRAINT fk_signalement_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    CONSTRAINT fk_signalement_station
        FOREIGN KEY (id_station) REFERENCES station(id_station),
    INDEX idx_signalement_station_date (id_station, date_detection),
    INDEX idx_signalement_velo_date (numero_serie, date_detection)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS operateur (
    id_operateur INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_prenom VARCHAR(160) NOT NULL,
    email VARCHAR(255) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    role_operateur ENUM('maintenance', 'regulation', 'administration') NOT NULL,
    CONSTRAINT uq_operateur_email UNIQUE (email)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS intervention (
    id_intervention INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    type_action VARCHAR(120) NOT NULL,
    id_signalement INT UNSIGNED NOT NULL,
    id_operateur INT UNSIGNED NOT NULL,
    CONSTRAINT fk_intervention_signalement
        FOREIGN KEY (id_signalement) REFERENCES signalement(id_signalement),
    CONSTRAINT fk_intervention_operateur
        FOREIGN KEY (id_operateur) REFERENCES operateur(id_operateur),
    CONSTRAINT ck_intervention_dates
        CHECK (date_fin IS NULL OR date_fin >= date_debut),
    INDEX idx_intervention_signalement (id_signalement),
    INDEX idx_intervention_operateur_date (id_operateur, date_debut)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS tournee_regulation (
    id_tournee INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_tournee DATE NOT NULL,
    immatriculation_vehicule VARCHAR(20) NOT NULL,
    id_operateur INT UNSIGNED NOT NULL,
    CONSTRAINT fk_tournee_operateur
        FOREIGN KEY (id_operateur) REFERENCES operateur(id_operateur),
    INDEX idx_tournee_operateur_date (id_operateur, date_tournee)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS mouvement_regulation (
    id_mouvement INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL,
    nature ENUM('retrait', 'depot') NOT NULL,
    id_tournee INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    id_station INT UNSIGNED NOT NULL,
    CONSTRAINT fk_mouvement_tournee
        FOREIGN KEY (id_tournee) REFERENCES tournee_regulation(id_tournee),
    CONSTRAINT fk_mouvement_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    CONSTRAINT fk_mouvement_station
        FOREIGN KEY (id_station) REFERENCES station(id_station),
    INDEX idx_mouvement_tournee_date (id_tournee, date_heure),
    INDEX idx_mouvement_station_date (id_station, date_heure)
) ENGINE = InnoDB;

-- ============================================================
-- HISTORISATION
-- ============================================================

CREATE TABLE IF NOT EXISTS historique_position (
    id_historique_position INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ancienne_borne INT UNSIGNED NULL,
    nouvelle_borne INT UNSIGNED NULL,
    type_deplacement VARCHAR(80) NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    CONSTRAINT fk_historique_position_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    CONSTRAINT fk_historique_position_ancienne_borne
        FOREIGN KEY (ancienne_borne) REFERENCES borne(id_borne),
    CONSTRAINT fk_historique_position_nouvelle_borne
        FOREIGN KEY (nouvelle_borne) REFERENCES borne(id_borne),
    INDEX idx_historique_position_velo_date (numero_serie, date_heure)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS historique_etat (
    id_historique_etat INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ancien_etat VARCHAR(30) NULL,
    nouvel_etat VARCHAR(30) NOT NULL,
    motif_changement VARCHAR(150) NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    CONSTRAINT fk_historique_etat_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    INDEX idx_historique_etat_velo_date (numero_serie, date_heure)
) ENGINE = InnoDB;

-- ============================================================
-- VUES : EXPLOITATION ET STATISTIQUES
-- ============================================================

-- Résume la disponibilité de chaque station.
CREATE OR REPLACE VIEW v_etat_station AS
SELECT s.id_station,
       s.nom,
       s.etat,
       s.capacite_bornes,
       COUNT(b.id_borne) AS nombre_bornes,
       COALESCE(SUM(b.etat = 'disponible' AND v.numero_serie IS NULL), 0) AS bornes_libres,
       COALESCE(SUM(v.etat = 'disponible'), 0) AS velos_disponibles,
       COALESCE(SUM(c.libelle = 'mecanique' AND v.etat = 'disponible'), 0) AS velos_mecaniques_disponibles,
       COALESCE(SUM(c.libelle = 'electrique' AND v.etat = 'disponible'), 0) AS velos_electriques_disponibles,
       CASE
           WHEN COALESCE(SUM(b.etat = 'disponible' AND v.numero_serie IS NULL), 0) <= s.seuil_bornes_min THEN 'saturee'
           WHEN COALESCE(SUM(v.etat = 'disponible'), 0) <= s.seuil_velos_min THEN 'vide'
           ELSE 'normale'
       END AS statut_alerte
FROM station s
LEFT JOIN borne b ON b.id_station = s.id_station
LEFT JOIN velo v ON v.id_borne_actuelle = b.id_borne
LEFT JOIN categorie_velo c ON c.id_categorie = v.id_categorie
GROUP BY s.id_station, s.nom, s.etat, s.capacite_bornes, s.seuil_velos_min, s.seuil_bornes_min;

-- Donne le nombre de trajets et les recettes par jour.
CREATE OR REPLACE VIEW v_statistiques_journalieres AS
SELECT DATE(date_depart) AS jour,
       COUNT(*) AS nombre_trajets,
       AVG(duree_minutes) AS duree_moyenne_minutes,
       SUM(cout_facture) AS recettes
FROM trajet
WHERE date_arrivee IS NOT NULL
GROUP BY DATE(date_depart);

-- Calcule les recettes liées à chaque formule.
CREATE OR REPLACE VIEW v_recettes_par_formule AS
SELECT f.id_formule,
       f.nom AS formule,
       COUNT(t.id_trajet) AS nombre_trajets,
       COALESCE(SUM(t.cout_facture), 0) AS recettes
FROM formule f
LEFT JOIN abonnement a ON a.id_formule = f.id_formule
LEFT JOIN trajet t ON t.id_usager = a.id_usager
                 AND DATE(t.date_depart) BETWEEN a.date_debut AND a.date_fin
                 AND t.date_arrivee IS NOT NULL
GROUP BY f.id_formule, f.nom;

-- ============================================================
-- FONCTION METIER : CALCUL DU COUT D'UN TRAJET
-- ============================================================

DELIMITER $$

-- Calcule le prix d'un trajet avec la formule active de l'usager.
CREATE FUNCTION fn_calculer_cout_trajet (
    p_id_usager INT UNSIGNED,
    p_numero_serie VARCHAR(50),
    p_date_depart DATETIME,
    p_date_arrivee DATETIME
)
RETURNS DECIMAL(10,2)
READS SQL DATA
BEGIN
    DECLARE v_prix_base DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_duree_gratuite INT UNSIGNED DEFAULT 0;
    DECLARE v_tarif_tranche DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_supplement_electrique DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_est_electrique BOOLEAN DEFAULT FALSE;
    DECLARE v_duree_minutes INT DEFAULT 0;
    DECLARE v_formule_trouvee BOOLEAN DEFAULT TRUE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_formule_trouvee = FALSE;

    SELECT f.prix_base,
           f.duree_gratuite_minutes,
           f.tarif_par_tranche,
           f.supplement_electrique,
           (c.libelle = 'electrique')
      INTO v_prix_base,
           v_duree_gratuite,
           v_tarif_tranche,
           v_supplement_electrique,
           v_est_electrique
      FROM abonnement a
      JOIN formule f ON f.id_formule = a.id_formule
      JOIN velo v ON v.numero_serie = p_numero_serie
      JOIN categorie_velo c ON c.id_categorie = v.id_categorie
     WHERE a.id_usager = p_id_usager
       AND DATE(p_date_depart) BETWEEN a.date_debut AND a.date_fin
     ORDER BY a.date_debut DESC
     LIMIT 1;

    IF NOT v_formule_trouvee THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Aucune formule active pour ce trajet';
    END IF;

    SET v_duree_minutes = GREATEST(TIMESTAMPDIFF(MINUTE, p_date_depart, p_date_arrivee), 0);

    RETURN v_prix_base
         + CEILING(
               GREATEST(
                   CAST(v_duree_minutes AS SIGNED) - CAST(v_duree_gratuite AS SIGNED),
                   0
               ) / 30.0
           ) * v_tarif_tranche
         + IF(v_est_electrique, v_supplement_electrique, 0.00);
END$$

DELIMITER ;

-- ============================================================
-- AUTOMATISATION : TRIGGERS
-- ============================================================

DELIMITER $$

-- Ajoute l'état et la position de départ dans les historiques.
CREATE TRIGGER trg_velo_ai_historique_etat
AFTER INSERT ON velo
FOR EACH ROW
BEGIN
    INSERT INTO historique_etat (nouvel_etat, motif_changement, numero_serie)
    VALUES (NEW.etat, 'Mise en service du vélo', NEW.numero_serie);

    IF NEW.id_borne_actuelle IS NOT NULL THEN
        INSERT INTO historique_position (nouvelle_borne, type_deplacement, numero_serie)
        VALUES (NEW.id_borne_actuelle, 'Mise en station initiale', NEW.numero_serie);
    END IF;
END$$

-- Historise tout changement d'état ou de borne d'un vélo.
CREATE TRIGGER trg_velo_au_historiser
AFTER UPDATE ON velo
FOR EACH ROW
BEGIN
    IF NOT (OLD.etat <=> NEW.etat) THEN
        INSERT INTO historique_etat (ancien_etat, nouvel_etat, motif_changement, numero_serie)
        VALUES (OLD.etat, NEW.etat, 'Modification de l''état du vélo', NEW.numero_serie);
    END IF;

    IF NOT (OLD.id_borne_actuelle <=> NEW.id_borne_actuelle) THEN
        INSERT INTO historique_position (ancienne_borne, nouvelle_borne, type_deplacement, numero_serie)
        VALUES (
            OLD.id_borne_actuelle,
            NEW.id_borne_actuelle,
            CASE
                WHEN NEW.id_borne_actuelle IS NULL THEN 'Sortie de borne'
                WHEN OLD.id_borne_actuelle IS NULL THEN 'Mise en borne'
                ELSE 'Changement de borne'
            END,
            NEW.numero_serie
        );
    END IF;
END$$

-- Vérifie qu'une réservation active est possible avant de l'ajouter.
CREATE TRIGGER trg_reservation_bi_verifier_disponibilite
BEFORE INSERT ON reservation
FOR EACH ROW
BEGIN
    DECLARE v_etat VARCHAR(30);
    DECLARE v_reservation_usager INT DEFAULT 0;
    DECLARE v_reservation_velo INT DEFAULT 0;

    SELECT etat INTO v_etat
    FROM velo
    WHERE numero_serie = NEW.numero_serie;

    IF NEW.statut = 'active' AND v_etat <> 'disponible' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Le vélo choisi n''est pas disponible à la réservation';
    END IF;

    IF NEW.statut = 'active' THEN
        SELECT COUNT(*) INTO v_reservation_usager
        FROM reservation
        WHERE id_usager = NEW.id_usager
          AND statut = 'active';

        SELECT COUNT(*) INTO v_reservation_velo
        FROM reservation
        WHERE numero_serie = NEW.numero_serie
          AND statut = 'active';

        IF v_reservation_usager > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Un usager ne peut avoir qu''une réservation active';
        END IF;

        IF v_reservation_velo > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Le vélo possède déjà une réservation active';
        END IF;
    END IF;
END$$

-- Empêche d'ajouter plus de bornes que la capacité de la station.
CREATE TRIGGER trg_borne_bi_respecter_capacite
BEFORE INSERT ON borne
FOR EACH ROW
BEGIN
    DECLARE v_capacite INT UNSIGNED;
    DECLARE v_nombre_bornes INT UNSIGNED;

    SELECT capacite_bornes INTO v_capacite
    FROM station
    WHERE id_station = NEW.id_station;

    SELECT COUNT(*) INTO v_nombre_bornes
    FROM borne
    WHERE id_station = NEW.id_station;

    IF v_nombre_bornes >= v_capacite THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La capacité en bornes de la station est atteinte';
    END IF;
END$$

-- Passe le vélo à l'état réservé après une réservation active.
CREATE TRIGGER trg_reservation_ai_reserver_velo
AFTER INSERT ON reservation
FOR EACH ROW
BEGIN
    IF NEW.statut = 'active' THEN
        UPDATE velo
        SET etat = 'reserve'
        WHERE numero_serie = NEW.numero_serie;
    END IF;
END$$

-- Libère le vélo quand une réservation est annulée ou expire.
CREATE TRIGGER trg_reservation_au_liberer_velo
AFTER UPDATE ON reservation
FOR EACH ROW
BEGIN
    DECLARE v_reservation_active INT DEFAULT 0;
    DECLARE v_trajet_ouvert INT DEFAULT 0;

    IF OLD.statut = 'active' AND NEW.statut <> 'active' THEN
        SELECT COUNT(*) INTO v_reservation_active
        FROM reservation
        WHERE numero_serie = NEW.numero_serie
          AND statut = 'active';

        SELECT COUNT(*) INTO v_trajet_ouvert
        FROM trajet
        WHERE numero_serie = NEW.numero_serie
          AND date_arrivee IS NULL;

        IF v_reservation_active = 0 AND v_trajet_ouvert = 0 THEN
            UPDATE velo
            SET etat = 'disponible'
            WHERE numero_serie = NEW.numero_serie
              AND etat = 'reserve';
        END IF;
    END IF;
END$$

-- Contrôle les règles avant de démarrer un trajet.
CREATE TRIGGER trg_trajet_bi_controler_emprunt
BEFORE INSERT ON trajet
FOR EACH ROW
BEGIN
    DECLARE v_etat_velo VARCHAR(30);
    DECLARE v_borne_actuelle INT UNSIGNED;
    DECLARE v_station_borne INT UNSIGNED;
    DECLARE v_trajet_usager INT DEFAULT 0;
    DECLARE v_trajet_velo INT DEFAULT 0;
    DECLARE v_abonnement_actif INT DEFAULT 0;
    DECLARE v_reservation_autre_usager INT DEFAULT 0;

    SELECT etat, id_borne_actuelle
      INTO v_etat_velo, v_borne_actuelle
      FROM velo
     WHERE numero_serie = NEW.numero_serie;

    IF v_etat_velo NOT IN ('disponible', 'reserve') THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Un vélo indisponible ou défectueux ne peut pas être emprunté';
    END IF;

    SELECT COUNT(*) INTO v_trajet_usager
    FROM trajet
    WHERE id_usager = NEW.id_usager
      AND date_arrivee IS NULL;

    SELECT COUNT(*) INTO v_trajet_velo
    FROM trajet
    WHERE numero_serie = NEW.numero_serie
      AND date_arrivee IS NULL;

    IF v_trajet_usager > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Un usager ne peut emprunter qu''un vélo à la fois';
    END IF;

    IF v_trajet_velo > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Le vélo est déjà en location';
    END IF;

    SELECT COUNT(*) INTO v_abonnement_actif
    FROM abonnement
    WHERE id_usager = NEW.id_usager
      AND DATE(NEW.date_depart) BETWEEN date_debut AND date_fin;

    IF v_abonnement_actif = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Un abonnement valide est nécessaire pour emprunter un vélo';
    END IF;

    SELECT COUNT(*) INTO v_reservation_autre_usager
    FROM reservation
    WHERE numero_serie = NEW.numero_serie
      AND statut = 'active'
      AND id_usager <> NEW.id_usager;

    IF v_reservation_autre_usager > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Le vélo est réservé par un autre usager';
    END IF;

    IF v_borne_actuelle IS NULL OR v_borne_actuelle <> NEW.id_borne_depart THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La borne de départ ne correspond pas à la position actuelle du vélo';
    END IF;

    SELECT id_station INTO v_station_borne
    FROM borne
    WHERE id_borne = NEW.id_borne_depart;

    IF v_station_borne <> NEW.id_station_depart THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La borne de départ doit appartenir à la station de départ';
    END IF;
END$$

-- Vérifie la restitution puis calcule durée, coût et paiement.
CREATE TRIGGER trg_trajet_bu_controler_dates
BEFORE UPDATE ON trajet
FOR EACH ROW
BEGIN
    DECLARE v_station_borne_arrivee INT UNSIGNED;
    DECLARE v_borne_occupee INT DEFAULT 0;
    DECLARE v_solde DECIMAL(10,2);

    IF NEW.date_arrivee IS NOT NULL AND NEW.date_arrivee < NEW.date_depart THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La date d''arrivée doit être postérieure à la date de départ';
    END IF;

    IF OLD.date_arrivee IS NULL AND NEW.date_arrivee IS NOT NULL THEN
        IF NEW.id_station_arrivee IS NULL OR NEW.id_borne_arrivee IS NULL THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Une station et une borne d''arrivée sont obligatoires pour restituer le vélo';
        END IF;

        SELECT id_station INTO v_station_borne_arrivee
        FROM borne
        WHERE id_borne = NEW.id_borne_arrivee;

        IF v_station_borne_arrivee <> NEW.id_station_arrivee THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La borne d''arrivée doit appartenir à la station d''arrivée';
        END IF;

        SELECT COUNT(*) INTO v_borne_occupee
        FROM velo
        WHERE id_borne_actuelle = NEW.id_borne_arrivee
          AND numero_serie <> NEW.numero_serie;

        IF v_borne_occupee > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'La borne d''arrivée est déjà occupée';
        END IF;

        SET NEW.duree_minutes = TIMESTAMPDIFF(MINUTE, NEW.date_depart, NEW.date_arrivee);
        SET NEW.cout_facture = fn_calculer_cout_trajet(
            NEW.id_usager,
            NEW.numero_serie,
            NEW.date_depart,
            NEW.date_arrivee
        );

        SELECT solde_portefeuille INTO v_solde
        FROM usager
        WHERE id_usager = NEW.id_usager;

        IF v_solde < NEW.cout_facture THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Solde insuffisant pour payer le trajet';
        END IF;

        SET NEW.statut_paiement = 'payee';
        SET NEW.date_paiement = NOW();
    END IF;
END$$

-- Passe le vélo en cours de location et utilise sa réservation si besoin.
CREATE TRIGGER trg_trajet_ai_demarrer_location
AFTER INSERT ON trajet
FOR EACH ROW
BEGIN
    UPDATE reservation
    SET statut = 'utilisee'
    WHERE id_usager = NEW.id_usager
      AND numero_serie = NEW.numero_serie
      AND statut = 'active';

    UPDATE velo
    SET etat = CASE WHEN NEW.date_arrivee IS NULL THEN 'en_cours' ELSE 'disponible' END,
        id_borne_actuelle = NULL
    WHERE numero_serie = NEW.numero_serie;
END$$

-- Remet le vélo sur la borne d'arrivée et débite le portefeuille.
CREATE TRIGGER trg_trajet_au_restituer_velo
AFTER UPDATE ON trajet
FOR EACH ROW
BEGIN
    IF OLD.date_arrivee IS NULL AND NEW.date_arrivee IS NOT NULL THEN
        UPDATE velo
        SET etat = 'disponible',
            id_borne_actuelle = NEW.id_borne_arrivee
        WHERE numero_serie = NEW.numero_serie;

        IF NEW.cout_facture > 0 THEN
            UPDATE usager
            SET solde_portefeuille = solde_portefeuille - NEW.cout_facture
            WHERE id_usager = NEW.id_usager;

            INSERT INTO operation_portefeuille (nature, montant, id_usager, id_trajet)
            VALUES ('paiement_trajet', -NEW.cout_facture, NEW.id_usager, NEW.id_trajet);
        END IF;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- EVENEMENT : expiration des réservations après 15 minutes
-- L'administrateur MySQL doit avoir activé event_scheduler=ON.
-- ============================================================

DELIMITER $$

-- Expire les réservations dépassant 15 minutes et libère les vélos concernés.
CREATE EVENT IF NOT EXISTS ev_expirer_reservations
ON SCHEDULE EVERY 1 MINUTE
DO
BEGIN
    UPDATE reservation
    SET statut = 'expiree'
    WHERE statut = 'active'
      AND date_reservation < NOW() - INTERVAL 15 MINUTE;

    UPDATE velo v
    LEFT JOIN reservation r
      ON r.numero_serie = v.numero_serie
     AND r.statut = 'active'
    LEFT JOIN trajet t
      ON t.numero_serie = v.numero_serie
     AND t.date_arrivee IS NULL
    SET v.etat = 'disponible'
    WHERE v.etat = 'reserve'
      AND r.id_reservation IS NULL
      AND t.id_trajet IS NULL;
END$$

DELIMITER ;

-- Vérification de l'événement (à exécuter après SET GLOBAL event_scheduler = ON) :
-- SHOW EVENTS FROM velo_libre_service;
