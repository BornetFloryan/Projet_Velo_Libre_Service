-- Comparaison d'une même requête avant/après index composé.
-- À lancer après velo_libre_service_mysql.sql puis donnees_test.sql.
-- La procédure de mesure est supprimée à la fin.

USE velo_libre_service;

DROP TEMPORARY TABLE IF EXISTS resultat_benchmark;
DROP PROCEDURE IF EXISTS mesurer_requete_historique;

CREATE TEMPORARY TABLE resultat_benchmark (
    scenario VARCHAR(80) NOT NULL,
    repetitions INT NOT NULL,
    lignes_retournees INT NOT NULL,
    duree_totale_ms DECIMAL(12,3) NOT NULL,
    duree_moyenne_ms DECIMAL(12,3) NOT NULL
);

DELIMITER $$

CREATE PROCEDURE mesurer_requete_historique(IN p_scenario VARCHAR(80))
BEGIN
    DECLARE v_compteur INT DEFAULT 0;
    DECLARE v_repetitions INT DEFAULT 100;
    DECLARE v_lignes INT DEFAULT 0;
    DECLARE v_debut DATETIME(6);
    DECLARE v_duree_ms DECIMAL(12,3);

    SET v_debut = NOW(6);

    SELECT CASE
        WHEN COUNT(*) > 50000 THEN 3
        ELSE 20
    END INTO v_repetitions
    FROM historique_position;

    WHILE v_compteur < v_repetitions DO
        SELECT COUNT(*) INTO v_lignes
        FROM (
            SELECT h.id_historique_position
            FROM historique_position h
            LEFT JOIN borne b_depart ON b_depart.id_borne = h.ancienne_borne
            LEFT JOIN station station_depart ON station_depart.id_station = b_depart.id_station
            LEFT JOIN borne b_arrivee ON b_arrivee.id_borne = h.nouvelle_borne
            LEFT JOIN station station_arrivee ON station_arrivee.id_station = b_arrivee.id_station
            WHERE h.numero_serie = 'VELO-001'
            ORDER BY h.date_heure DESC
            LIMIT 500
        ) AS historique;

        SET v_compteur = v_compteur + 1;
    END WHILE;

    SET v_duree_ms = TIMESTAMPDIFF(MICROSECOND, v_debut, NOW(6)) / 1000;

    INSERT INTO resultat_benchmark (
        scenario, repetitions, lignes_retournees, duree_totale_ms, duree_moyenne_ms
    ) VALUES (
        p_scenario, v_compteur, v_lignes, v_duree_ms, v_duree_ms / v_compteur
    );
END$$

DELIMITER ;

-- Scénario sans index composé : index simple conservé pour la clé étrangère.
CREATE INDEX IF NOT EXISTS idx_historique_position_velo_simple
ON historique_position (numero_serie);

ALTER TABLE historique_position DROP INDEX IF EXISTS idx_historique_position_velo_date;

CALL mesurer_requete_historique('Sans index compose');

-- Scénario optimisé : retour à l'index composé utilisé par l'application.
CREATE INDEX IF NOT EXISTS idx_historique_position_velo_date
ON historique_position (numero_serie, date_heure);

CALL mesurer_requete_historique('Avec index compose');

SELECT *
FROM resultat_benchmark
ORDER BY CASE scenario
    WHEN 'Sans index compose' THEN 1
    ELSE 2
END;

EXPLAIN FORMAT=JSON
SELECT h.date_heure, h.type_deplacement,
       station_depart.nom AS ancienne_station,
       station_arrivee.nom AS nouvelle_station
FROM historique_position h
LEFT JOIN borne b_depart ON b_depart.id_borne = h.ancienne_borne
LEFT JOIN station station_depart ON station_depart.id_station = b_depart.id_station
LEFT JOIN borne b_arrivee ON b_arrivee.id_borne = h.nouvelle_borne
LEFT JOIN station station_arrivee ON station_arrivee.id_station = b_arrivee.id_station
WHERE h.numero_serie = 'VELO-001'
ORDER BY h.date_heure DESC;

DROP PROCEDURE mesurer_requete_historique;
