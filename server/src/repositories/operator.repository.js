const { many, value } = require("../db");

async function overview() {
  const [
    availableBikes,
    openReports,
    openInterventions,
    recentReports,
    overdueTrips,
  ] = await Promise.all([
    value("SELECT COUNT(*) FROM velo WHERE etat = 'disponible'"),
    value(
      "SELECT COUNT(*) FROM signalement s LEFT JOIN intervention i ON i.id_signalement = s.id_signalement WHERE i.id_intervention IS NULL",
    ),
    value("SELECT COUNT(*) FROM intervention WHERE date_fin IS NULL"),
    many(
      `SELECT s.*, st.nom AS station_nom, CONCAT(u.prenom, ' ', u.nom) AS usager_nom
      FROM signalement s
      JOIN station st ON st.id_station = s.id_station
      JOIN usager u ON u.id_usager = s.id_usager
      ORDER BY s.date_detection DESC LIMIT 8`,
    ),
    many(
      `SELECT t.id_trajet, t.numero_serie, t.date_depart, TIMESTAMPDIFF(HOUR, t.date_depart, NOW()) AS duree_heures, CONCAT(u.prenom, ' ', u.nom) AS usager_nom
      FROM trajet t
      JOIN usager u ON u.id_usager = t.id_usager
      WHERE t.date_arrivee IS NULL
      AND t.date_depart < NOW() - INTERVAL 24 HOUR
      ORDER BY t.date_depart ASC`,
    ),
  ]);
  return {
    availableBikes,
    openReports,
    openInterventions,
    recentReports,
    overdueTrips,
  };
}

async function statistics() {
  const [
    dailyStats,
    formulaStats,
    topStations,
    totalRevenue,
    completedTrips,
    averageDuration,
  ] = await Promise.all([
    many(
      "SELECT * FROM v_statistiques_journalieres ORDER BY jour DESC LIMIT 14",
    ),
    many(
      "SELECT * FROM v_recettes_par_formule ORDER BY recettes DESC, formule",
    ),
    many(
      `SELECT s.nom, COUNT(t.id_trajet) AS nombre_trajets, COALESCE(SUM(t.cout_facture),0) AS recettes FROM station s LEFT JOIN trajet t ON t.id_station_depart=s.id_station AND t.date_arrivee IS NOT NULL GROUP BY s.id_station,s.nom ORDER BY nombre_trajets DESC,s.nom`,
    ),
    value(
      "SELECT COALESCE(SUM(cout_facture),0) FROM trajet WHERE date_arrivee IS NOT NULL",
    ),
    value("SELECT COUNT(*) FROM trajet WHERE date_arrivee IS NOT NULL"),
    value(
      "SELECT COALESCE(AVG(duree_minutes),0) FROM trajet WHERE date_arrivee IS NOT NULL",
    ),
  ]);
  return {
    dailyStats,
    formulaStats,
    topStations,
    totalRevenue,
    completedTrips,
    averageDuration,
  };
}

async function history(serial) {
  const bikes = await many('SELECT numero_serie FROM velo ORDER BY numero_serie');
  const selectedSerial = serial || bikes[0]?.numero_serie || null;
  if (!selectedSerial) return { bikes, selectedSerial, positions: [], states: [] };

  const [positions, states] = await Promise.all([
    many(`SELECT h.date_heure, h.type_deplacement,
                 oldStation.nom AS ancienne_station, newStation.nom AS nouvelle_station
          FROM historique_position h
          LEFT JOIN borne oldBorne ON oldBorne.id_borne = h.ancienne_borne
          LEFT JOIN station oldStation ON oldStation.id_station = oldBorne.id_station
          LEFT JOIN borne newBorne ON newBorne.id_borne = h.nouvelle_borne
          LEFT JOIN station newStation ON newStation.id_station = newBorne.id_station
          WHERE h.numero_serie = :serial
          ORDER BY h.date_heure DESC`, { serial: selectedSerial }),
    many(`SELECT date_heure, ancien_etat, nouvel_etat, motif_changement
          FROM historique_etat
          WHERE numero_serie = :serial
          ORDER BY date_heure DESC`, { serial: selectedSerial })
  ]);

  return { bikes, selectedSerial, positions, states };
}

module.exports = { overview, statistics, history };
