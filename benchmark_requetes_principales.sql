-- Mesure avant/après index des requêtes principales.
-- À lancer après les trois scripts de données, dont donnees_benchmark_complet.sql.
-- Les résultats sont affichés à la fin dans resultat_benchmark_principal.

USE velo_libre_service;

DROP TEMPORARY TABLE IF EXISTS resultat_benchmark_principal;
DROP PROCEDURE IF EXISTS mesurer_requete_principale;

CREATE TEMPORARY TABLE resultat_benchmark_principal (
    requete VARCHAR(100) NOT NULL,
    scenario VARCHAR(40) NOT NULL,
    repetitions INT NOT NULL,
    lignes_retournees INT NOT NULL,
    duree_totale_ms DECIMAL(12,3) NOT NULL,
    duree_moyenne_ms DECIMAL(12,3) NOT NULL
);

DELIMITER $$

CREATE PROCEDURE mesurer_requete_principale(
    IN p_requete VARCHAR(100),
    IN p_scenario VARCHAR(40),
    IN p_sql TEXT,
    IN p_repetitions INT
)
BEGIN
    DECLARE v_compteur INT DEFAULT 0;
    DECLARE v_debut DATETIME(6);
    DECLARE v_duree_ms DECIMAL(12,3);

    SET @requete_benchmark = CONCAT(
        'SELECT COUNT(*) INTO @lignes_benchmark FROM (', p_sql, ') AS donnees'
    );
    PREPARE requete_mesuree FROM @requete_benchmark;

    SET v_debut = NOW(6);
    WHILE v_compteur < p_repetitions DO
        EXECUTE requete_mesuree;
        SET v_compteur = v_compteur + 1;
    END WHILE;
    SET v_duree_ms = TIMESTAMPDIFF(MICROSECOND, v_debut, NOW(6)) / 1000;

    DEALLOCATE PREPARE requete_mesuree;

    INSERT INTO resultat_benchmark_principal (
        requete, scenario, repetitions, lignes_retournees,
        duree_totale_ms, duree_moyenne_ms
    ) VALUES (
        p_requete, p_scenario, v_compteur, @lignes_benchmark,
        v_duree_ms, v_duree_ms / v_compteur
    );
END$$

DELIMITER ;

-- Scénario sans les index ciblés.
-- Les index simples conservent le support nécessaire aux clés étrangères ;
-- ils ne couvrent volontairement pas le tri par date.
CREATE INDEX idx_trajet_usager_simple ON trajet (id_usager);
CREATE INDEX idx_operation_usager_simple ON operation_portefeuille (id_usager);
CREATE INDEX idx_signalement_station_simple ON signalement (id_station);
CREATE INDEX idx_historique_position_velo_simple ON historique_position (numero_serie);

DROP INDEX idx_reservation_expiration ON reservation;
DROP INDEX idx_trajet_usager_date ON trajet;
DROP INDEX idx_operation_usager_date ON operation_portefeuille;
DROP INDEX idx_signalement_station_date ON signalement;
DROP INDEX idx_historique_position_velo_date ON historique_position;

CALL mesurer_requete_principale(
    'Réservations expirées', 'Sans index',
    'SELECT id_reservation FROM reservation WHERE statut = ''expiree'' AND date_reservation < NOW() - INTERVAL 15 MINUTE ORDER BY date_reservation DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Trajets d''un usager', 'Sans index',
    'SELECT id_trajet FROM trajet WHERE id_usager = 5 ORDER BY date_depart DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Opérations portefeuille', 'Sans index',
    'SELECT id_operation FROM operation_portefeuille WHERE id_usager = 6 ORDER BY date_heure DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Signalements station', 'Sans index',
    'SELECT id_signalement FROM signalement WHERE id_station = 14 ORDER BY date_detection DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Historique vélo', 'Sans index',
    'SELECT id_historique_position FROM historique_position WHERE numero_serie = ''VELO-001'' ORDER BY date_heure DESC LIMIT 500',
    3
);

-- Scénario optimisé : index alignés avec les filtres et les tris.
CREATE INDEX idx_reservation_expiration
ON reservation (statut, date_reservation);
CREATE INDEX idx_trajet_usager_date
ON trajet (id_usager, date_depart);
CREATE INDEX idx_operation_usager_date
ON operation_portefeuille (id_usager, date_heure);
CREATE INDEX idx_signalement_station_date
ON signalement (id_station, date_detection);
CREATE INDEX idx_historique_position_velo_date
ON historique_position (numero_serie, date_heure);
DROP INDEX idx_trajet_usager_simple ON trajet;
DROP INDEX idx_operation_usager_simple ON operation_portefeuille;
DROP INDEX idx_signalement_station_simple ON signalement;
DROP INDEX idx_historique_position_velo_simple ON historique_position;

CALL mesurer_requete_principale(
    'Réservations expirées', 'Avec index',
    'SELECT id_reservation FROM reservation WHERE statut = ''expiree'' AND date_reservation < NOW() - INTERVAL 15 MINUTE ORDER BY date_reservation DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Trajets d''un usager', 'Avec index',
    'SELECT id_trajet FROM trajet WHERE id_usager = 5 ORDER BY date_depart DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Opérations portefeuille', 'Avec index',
    'SELECT id_operation FROM operation_portefeuille WHERE id_usager = 6 ORDER BY date_heure DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Signalements station', 'Avec index',
    'SELECT id_signalement FROM signalement WHERE id_station = 14 ORDER BY date_detection DESC LIMIT 200',
    30
);
CALL mesurer_requete_principale(
    'Historique vélo', 'Avec index',
    'SELECT id_historique_position FROM historique_position WHERE numero_serie = ''VELO-001'' ORDER BY date_heure DESC LIMIT 500',
    3
);

SELECT *
FROM resultat_benchmark_principal
ORDER BY requete, FIELD(scenario, 'Sans index', 'Avec index');

EXPLAIN
SELECT id_historique_position
FROM historique_position
WHERE numero_serie = 'VELO-001'
ORDER BY date_heure DESC
LIMIT 500;

DROP PROCEDURE mesurer_requete_principale;
