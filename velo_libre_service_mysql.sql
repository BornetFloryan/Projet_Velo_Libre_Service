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
    id_categorie TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL,
    CONSTRAINT uq_categorie_velo_libelle UNIQUE (libelle),
    CONSTRAINT ck_categorie_velo_libelle
        CHECK (libelle IN ('mecanique', 'electrique'))
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS formule (
    id_formule INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(80) NOT NULL,
    prix_base DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    duree_gratuite_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
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
    capacite_bornes SMALLINT UNSIGNED NOT NULL,
    etat ENUM('ouverte', 'maintenance', 'fermee') NOT NULL DEFAULT 'ouverte',
    CONSTRAINT uq_station_nom UNIQUE (nom),
    CONSTRAINT ck_station_capacite CHECK (capacite_bornes > 0),
    CONSTRAINT ck_station_latitude CHECK (latitude BETWEEN -90 AND 90),
    CONSTRAINT ck_station_longitude CHECK (longitude BETWEEN -180 AND 180)
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
    niveau_batterie TINYINT UNSIGNED NULL,
    date_mise_service DATE NOT NULL,
    id_categorie TINYINT UNSIGNED NOT NULL,
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
    note_evaluation TINYINT UNSIGNED NULL,
    id_usager INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    id_station_depart INT UNSIGNED NOT NULL,
    id_station_arrivee INT UNSIGNED NULL,
    CONSTRAINT fk_trajet_usager
        FOREIGN KEY (id_usager) REFERENCES usager(id_usager),
    CONSTRAINT fk_trajet_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
    CONSTRAINT fk_trajet_station_depart
        FOREIGN KEY (id_station_depart) REFERENCES station(id_station),
    CONSTRAINT fk_trajet_station_arrivee
        FOREIGN KEY (id_station_arrivee) REFERENCES station(id_station),
    CONSTRAINT ck_trajet_dates
        CHECK (date_arrivee IS NULL OR date_arrivee >= date_depart),
    CONSTRAINT ck_trajet_cout CHECK (cout_facture >= 0),
    CONSTRAINT ck_trajet_note CHECK (note_evaluation IS NULL OR note_evaluation BETWEEN 1 AND 5),
    INDEX idx_trajet_usager_date (id_usager, date_depart),
    INDEX idx_trajet_velo_ouvert (numero_serie, date_arrivee)
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
    role_operateur ENUM('maintenance', 'regulation', 'administration') NOT NULL,
    CONSTRAINT uq_operateur_email UNIQUE (email)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS intervention (
    id_intervention INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NULL,
    type_action VARCHAR(120) NOT NULL,
    id_signalement INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(50) NOT NULL,
    id_operateur INT UNSIGNED NOT NULL,
    CONSTRAINT fk_intervention_signalement
        FOREIGN KEY (id_signalement) REFERENCES signalement(id_signalement),
    CONSTRAINT fk_intervention_velo
        FOREIGN KEY (numero_serie) REFERENCES velo(numero_serie),
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
    id_historique_position BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
    id_historique_etat BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
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
-- FONCTION METIER : CALCUL DU COUT D'UN TRAJET
-- ============================================================

DELIMITER $$

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
    DECLARE v_duree_gratuite SMALLINT UNSIGNED DEFAULT 0;
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
         + CEILING(GREATEST(v_duree_minutes - v_duree_gratuite, 0) / 30.0) * v_tarif_tranche
         + IF(v_est_electrique, v_supplement_electrique, 0.00);
END$$

DELIMITER ;

-- ============================================================
-- AUTOMATISATION : TRIGGERS
-- ============================================================

DELIMITER $$

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

CREATE TRIGGER trg_reservation_bi_verifier_disponibilite
BEFORE INSERT ON reservation
FOR EACH ROW
BEGIN
    DECLARE v_etat VARCHAR(30);

    SELECT etat INTO v_etat
    FROM velo
    WHERE numero_serie = NEW.numero_serie;

    IF NEW.statut = 'active' AND v_etat <> 'disponible' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Le vélo choisi n''est pas disponible à la réservation';
    END IF;
END$$

CREATE TRIGGER trg_borne_bi_respecter_capacite
BEFORE INSERT ON borne
FOR EACH ROW
BEGIN
    DECLARE v_capacite SMALLINT UNSIGNED;
    DECLARE v_nombre_bornes SMALLINT UNSIGNED;

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

CREATE TRIGGER trg_intervention_bi_coherence_signalement
BEFORE INSERT ON intervention
FOR EACH ROW
BEGIN
    DECLARE v_numero_serie_signalement VARCHAR(50);

    SELECT numero_serie INTO v_numero_serie_signalement
    FROM signalement
    WHERE id_signalement = NEW.id_signalement;

    IF v_numero_serie_signalement <> NEW.numero_serie THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Le vélo de l''intervention doit correspondre au vélo signalé';
    END IF;
END$$

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

CREATE TRIGGER trg_trajet_bu_controler_dates
BEFORE UPDATE ON trajet
FOR EACH ROW
BEGIN
    IF NEW.date_arrivee IS NOT NULL AND NEW.date_arrivee < NEW.date_depart THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La date d''arrivée doit être postérieure à la date de départ';
    END IF;

    IF OLD.date_arrivee IS NULL AND NEW.date_arrivee IS NOT NULL THEN
        SET NEW.cout_facture = fn_calculer_cout_trajet(
            NEW.id_usager,
            NEW.numero_serie,
            NEW.date_depart,
            NEW.date_arrivee
        );
    END IF;
END$$

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

CREATE TRIGGER trg_trajet_au_restituer_velo
AFTER UPDATE ON trajet
FOR EACH ROW
BEGIN
    IF OLD.date_arrivee IS NULL AND NEW.date_arrivee IS NOT NULL THEN
        UPDATE velo
        SET etat = 'disponible'
        WHERE numero_serie = NEW.numero_serie;
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- EVENEMENT : expiration des réservations après 15 minutes
-- L'administrateur MySQL doit avoir activé event_scheduler=ON.
-- ============================================================

DELIMITER $$

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
