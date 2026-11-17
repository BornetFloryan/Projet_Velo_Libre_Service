-- Jeu de charge pour la comparaison à l'oral.
-- À importer après velo_libre_service_mysql.sql et donnees_test.sql.
-- Ajoute 1 500 000 positions historiques pour SIM-001.

USE velo_libre_service;

DELETE FROM historique_position
WHERE type_deplacement = 'Charge de comparaison optimisation';

DROP TEMPORARY TABLE IF EXISTS nombres_charge;

CREATE TEMPORARY TABLE nombres_charge AS
SELECT
    unite.n
    + dizaine.n * 10
    + centaine.n * 100
    + millier.n * 1000
    + dizaine_millier.n * 10000
    + centaine_millier.n * 100000 AS n
FROM
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS unite
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS dizaine
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS centaine
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS millier
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS dizaine_millier
CROSS JOIN
    (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
     UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS centaine_millier;

INSERT INTO historique_position (
    date_heure, ancienne_borne, nouvelle_borne, type_deplacement, numero_serie
)
SELECT
    NOW() - INTERVAL n MINUTE,
    1 + MOD(n, 32),
    1 + MOD(n + 7, 32),
    'Charge de comparaison optimisation',
    'SIM-001'
FROM nombres_charge;

INSERT INTO historique_position (
    date_heure, ancienne_borne, nouvelle_borne, type_deplacement, numero_serie
)
SELECT
    NOW() - INTERVAL (n + 1000000) MINUTE,
    1 + MOD(n + 3, 32),
    1 + MOD(n + 10, 32),
    'Charge de comparaison optimisation',
    'SIM-001'
FROM nombres_charge
WHERE n < 500000;

DROP TEMPORARY TABLE nombres_charge;
