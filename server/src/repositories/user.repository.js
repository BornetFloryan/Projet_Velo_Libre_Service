const { one, many, value, pool } = require("../db");
async function dashboard(id) {
  const user = await one("SELECT * FROM usager WHERE id_usager = :id", { id });
  const openTrip = await one(
    `SELECT t.*, s.nom AS station_depart_nom, b.id_borne AS borne_depart 
    FROM trajet t 
    JOIN station s ON s.id_station=t.id_station_depart 
    JOIN borne b ON b.id_borne=t.id_borne_depart 
    WHERE t.id_usager=:id 
    AND t.date_arrivee IS NULL`,
    { id },
  );
  const reservation = await one(
    `SELECT r.*, b.id_borne, s.nom AS station_nom 
    FROM reservation r 
    LEFT JOIN velo v ON v.numero_serie=r.numero_serie 
    LEFT JOIN borne b ON b.id_borne=v.id_borne_actuelle 
    LEFT JOIN station s ON s.id_station=b.id_station 
    WHERE r.id_usager=:id 
    AND r.statut='active'`,
    { id },
  );
  const subscription = await one(
    `SELECT a.*, f.nom AS formule_nom, f.duree_gratuite_minutes 
    FROM abonnement a 
    JOIN formule f ON f.id_formule=a.id_formule 
    WHERE a.id_usager=:id 
    ORDER BY a.date_fin DESC LIMIT 1`,
    { id },
  );
  return { user, openTrip, reservation, subscription };
}
async function trips(id) {
  const openTrip = await one(
    `SELECT t.*, ds.nom AS station_depart_nom, db.id_borne AS borne_depart 
    FROM trajet t 
    JOIN station ds ON ds.id_station=t.id_station_depart 
    JOIN borne db ON db.id_borne=t.id_borne_depart 
    WHERE t.id_usager=:id 
    AND t.date_arrivee IS NULL`,
    { id },
  );
  const reservation = await one(
    `SELECT r.*, s.nom AS station_nom, b.id_borne 
    FROM reservation r 
    LEFT JOIN velo v ON v.numero_serie=r.numero_serie 
    LEFT JOIN borne b ON b.id_borne=v.id_borne_actuelle 
    LEFT JOIN station s ON s.id_station=b.id_station 
    WHERE r.id_usager=:id 
    ORDER BY r.date_reservation DESC LIMIT 1`,
    { id },
  );
  const history = await many(
    `SELECT t.*, ds.nom AS station_depart_nom, ar.nom AS station_arrivee_nom 
    FROM trajet t 
    JOIN station ds ON ds.id_station=t.id_station_depart 
    LEFT JOIN station ar ON ar.id_station=t.id_station_arrivee 
    WHERE t.id_usager=:id 
    ORDER BY t.date_depart DESC`,
    { id },
  );
  const stations = await many(
    "SELECT id_station, nom FROM station WHERE etat='en_service' ORDER BY nom",
  );
  const freeBores = await many(
    `SELECT b.id_borne, b.id_station, s.nom AS station_nom 
    FROM borne b 
    JOIN station s ON s.id_station=b.id_station 
    LEFT JOIN velo v ON v.id_borne_actuelle=b.id_borne 
    WHERE b.etat='disponible' 
    AND v.numero_serie IS NULL 
    ORDER BY s.nom,b.id_borne`,
  );
  return { openTrip, reservation, trips: history, stations, freeBores };
}
async function account(id) {
  const base = await dashboard(id);
  base.subscriptions = await many(
    `SELECT a.*, f.nom AS formule_nom, f.prix_base, f.duree_gratuite_minutes, f.tarif_par_tranche, f.supplement_electrique 
    FROM abonnement a 
    JOIN formule f ON f.id_formule=a.id_formule 
    WHERE a.id_usager=:id 
    ORDER BY a.date_debut DESC`,
    { id },
  );
  base.operations = await many(
    `SELECT o.*, t.numero_serie 
    FROM operation_portefeuille o 
    LEFT JOIN trajet t ON t.id_trajet=o.id_trajet 
    WHERE o.id_usager=:id 
    ORDER BY o.date_heure DESC LIMIT 20`,
    { id },
  );
  base.formulas = await many("SELECT * FROM formule ORDER BY prix_base, nom");
  return base;
}
module.exports = { pool, one, many, value, dashboard, trips, account };
