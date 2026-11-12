-- Jeu de données pour comparer les plans d'exécution.
-- À importer APRÈS velo_libre_service_mysql.sql et donnees_test.sql.
-- Le volume reste raisonnable : 12 000 positions + 12 000 changements d'état.
-- Les lignes sont réparties sur les 12 vélos SIM-* et couvrent environ 41 jours.

USE velo_libre_service;

-- Le script peut être relancé sans cumuler les données de charge.
DELETE FROM historique_position
WHERE type_deplacement = 'Simulation de charge';

DELETE FROM historique_etat
WHERE motif_changement = 'Simulation de charge';

DELIMITER $$

CREATE PROCEDURE charger_donnees_performance()
BEGIN
    DECLARE v_compteur INT DEFAULT 1;
    DECLARE v_velo VARCHAR(50);
    DECLARE v_ancienne_borne INT;
    DECLARE v_nouvelle_borne INT;

    WHILE v_compteur <= 12000 DO
        SET v_velo = CONCAT('SIM-', LPAD(1 + MOD(v_compteur - 1, 12), 3, '0'));
        SET v_ancienne_borne = 1 + MOD(v_compteur - 1, 32);
        SET v_nouvelle_borne = 1 + MOD(v_compteur + 6, 32);

        INSERT INTO historique_position (
            date_heure, ancienne_borne, nouvelle_borne, type_deplacement, numero_serie
        ) VALUES (
            NOW() - INTERVAL v_compteur * 5 MINUTE,
            v_ancienne_borne,
            v_nouvelle_borne,
            'Simulation de charge',
            v_velo
        );

        INSERT INTO historique_etat (
            date_heure, ancien_etat, nouvel_etat, motif_changement, numero_serie
        ) VALUES (
            NOW() - INTERVAL v_compteur * 5 MINUTE,
            IF(MOD(v_compteur, 2) = 0, 'disponible', 'maintenance'),
            IF(MOD(v_compteur, 2) = 0, 'maintenance', 'disponible'),
            'Simulation de charge',
            v_velo
        );

        SET v_compteur = v_compteur + 1;
    END WHILE;
END$$

DELIMITER ;

START TRANSACTION;
CALL charger_donnees_performance();
DROP PROCEDURE charger_donnees_performance;
COMMIT;

-- Plan attendu : MySQL utilise idx_historique_position_velo_date.
EXPLAIN
SELECT date_heure, type_deplacement
FROM historique_position
WHERE numero_serie = 'SIM-001'
ORDER BY date_heure DESC;

-- Comparaison pédagogique : même requête sans utiliser l'index, sans le supprimer.
EXPLAIN
SELECT date_heure, type_deplacement
FROM historique_position IGNORE INDEX (idx_historique_position_velo_date)
WHERE numero_serie = 'SIM-001'
ORDER BY date_heure DESC;
