-- Jeu de charge complet pour le rapport de performances.
-- À importer après :
--   1. velo_libre_service_mysql.sql
--   2. donnees_test.sql
--   3. donnees_charge_optimisation.sql
-- À utiliser sur une base de benchmark vide ou réinitialisée.

USE velo_libre_service;

DROP PROCEDURE IF EXISTS charger_donnees_benchmark_complet;

DELIMITER $$

CREATE PROCEDURE charger_donnees_benchmark_complet()
BEGIN
    DECLARE v_compteur INT DEFAULT 1;
    DECLARE v_borne_compteur INT;
    DECLARE v_station INT;
    DECLARE v_borne_depart INT;
    DECLARE v_borne_arrivee INT;
    DECLARE v_id_station_depart INT;
    DECLARE v_trajet INT;
    DECLARE v_signalement INT;
    DECLARE v_tournee INT;
    DECLARE v_velo VARCHAR(50);
    DECLARE v_usager INT;

    -- 100 stations et 100 vélos supplémentaires pour tester le réseau.
    WHILE v_compteur <= 100 DO
        INSERT INTO station (
            nom, adresse, latitude, longitude, capacite_bornes,
            etat, seuil_velos_min, seuil_bornes_min
        ) VALUES (
            CONCAT('Station benchmark ', LPAD(v_compteur, 3, '0')),
            CONCAT('Adresse benchmark ', v_compteur),
            47.500000 + v_compteur / 10000,
            6.700000 + v_compteur / 10000,
            8, 'en_service', 2, 2
        );
        SET v_station = LAST_INSERT_ID();

        SET v_borne_compteur = 1;
        WHILE v_borne_compteur <= 8 DO
            INSERT INTO borne (etat, id_station)
            VALUES ('disponible', v_station);
            SET v_borne_compteur = v_borne_compteur + 1;
        END WHILE;

        SELECT MIN(id_borne) INTO v_borne_depart
        FROM borne
        WHERE id_station = v_station;

        INSERT INTO velo (
            numero_serie, etat, niveau_batterie, date_mise_service,
            id_categorie, id_borne_actuelle
        ) VALUES (
            CONCAT('BENCH-', LPAD(v_compteur, 3, '0')),
            'disponible',
            IF(MOD(v_compteur, 2) = 0, 75, NULL),
            CURDATE() - INTERVAL 180 DAY,
            IF(MOD(v_compteur, 2) = 0, 2, 1),
            v_borne_depart
        );

        SET v_compteur = v_compteur + 1;
    END WHILE;

    -- 10 000 trajets terminés, réservations et opérations répartis
    -- sur les huit comptes de simulation et les 100 vélos benchmark.
    SET v_compteur = 1;
    WHILE v_compteur <= 10000 DO
        SET v_velo = CONCAT('BENCH-', LPAD(1 + MOD(v_compteur - 1, 100), 3, '0'));
        SET v_usager = 5 + MOD(v_compteur - 1, 8);

        SELECT v.id_borne_actuelle, b.id_station
          INTO v_borne_depart, v_id_station_depart
          FROM velo v
          JOIN borne b ON b.id_borne = v.id_borne_actuelle
         WHERE v.numero_serie = v_velo;

        SELECT b.id_borne INTO v_borne_arrivee
        FROM borne b
        LEFT JOIN velo v ON v.id_borne_actuelle = b.id_borne
        WHERE b.id_station = v_id_station_depart
          AND b.id_borne <> v_borne_depart
          AND v.numero_serie IS NULL
        ORDER BY b.id_borne
        LIMIT 1;

        INSERT INTO trajet (
            date_depart, id_usager, numero_serie,
            id_station_depart, id_borne_depart
        ) VALUES (
            NOW() - INTERVAL v_compteur * 4 MINUTE,
            v_usager, v_velo, v_id_station_depart, v_borne_depart
        );
        SET v_trajet = LAST_INSERT_ID();

        UPDATE trajet
        SET date_arrivee = DATE_ADD(date_depart, INTERVAL 10 MINUTE),
            id_station_arrivee = v_id_station_depart,
            id_borne_arrivee = v_borne_arrivee
        WHERE id_trajet = v_trajet;

        INSERT INTO reservation (
            date_reservation, statut, id_usager, numero_serie
        ) VALUES (
            NOW() - INTERVAL v_compteur * 4 MINUTE,
            CASE MOD(v_compteur, 3)
                WHEN 0 THEN 'expiree'
                WHEN 1 THEN 'annulee'
                ELSE 'utilisee'
            END,
            v_usager, v_velo
        );

        IF MOD(v_compteur, 10) = 0 THEN
            UPDATE usager
            SET solde_portefeuille = solde_portefeuille + 5.00
            WHERE id_usager = v_usager;

            INSERT INTO operation_portefeuille (
                date_heure, nature, montant, id_usager
            ) VALUES (
                NOW() - INTERVAL v_compteur * 4 MINUTE,
                'rechargement', 5.00, v_usager
            );

            INSERT INTO signalement (
                date_detection, description, id_usager,
                numero_serie, id_station
            ) VALUES (
                NOW() - INTERVAL v_compteur * 4 MINUTE,
                CONCAT('Signalement benchmark ', v_compteur),
                v_usager, v_velo, v_id_station_depart
            );
            SET v_signalement = LAST_INSERT_ID();

            IF MOD(v_compteur, 20) = 0 THEN
                INSERT INTO intervention (
                    date_debut, date_fin, type_action,
                    id_signalement, id_operateur
                ) VALUES (
                    NOW() - INTERVAL v_compteur * 4 MINUTE,
                    NOW() - INTERVAL v_compteur * 4 MINUTE + INTERVAL 20 MINUTE,
                    'Intervention benchmark terminée',
                    v_signalement, 1
                );
            END IF;
        END IF;

        IF MOD(v_compteur, 50) = 0 THEN
            INSERT INTO tournee_regulation (
                date_tournee, immatriculation_vehicule, id_operateur
            ) VALUES (
                DATE(NOW() - INTERVAL v_compteur * 4 MINUTE),
                CONCAT('BM-', LPAD(v_compteur, 5, '0')),
                2
            );
            SET v_tournee = LAST_INSERT_ID();

            INSERT INTO mouvement_regulation (
                date_heure, nature, id_tournee, numero_serie, id_station
            ) VALUES
                (NOW() - INTERVAL v_compteur * 4 MINUTE, 'retrait',
                 v_tournee, v_velo, v_id_station_depart),
                (NOW() - INTERVAL v_compteur * 4 MINUTE + INTERVAL 15 MINUTE, 'depot',
                 v_tournee, v_velo, v_id_station_depart);
        END IF;

        SET v_compteur = v_compteur + 1;
    END WHILE;
END$$

DELIMITER ;

START TRANSACTION;
CALL charger_donnees_benchmark_complet();
DROP PROCEDURE charger_donnees_benchmark_complet;
COMMIT;

-- Contrôle du volume obtenu.
SELECT 'trajet' AS table_name, COUNT(*) AS total FROM trajet
UNION ALL SELECT 'reservation', COUNT(*) FROM reservation
UNION ALL SELECT 'operation_portefeuille', COUNT(*) FROM operation_portefeuille
UNION ALL SELECT 'signalement', COUNT(*) FROM signalement
UNION ALL SELECT 'intervention', COUNT(*) FROM intervention
UNION ALL SELECT 'tournee_regulation', COUNT(*) FROM tournee_regulation
UNION ALL SELECT 'mouvement_regulation', COUNT(*) FROM mouvement_regulation
UNION ALL SELECT 'historique_position', COUNT(*) FROM historique_position
UNION ALL SELECT 'historique_etat', COUNT(*) FROM historique_etat;
