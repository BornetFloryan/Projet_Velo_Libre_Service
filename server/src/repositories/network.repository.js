const { many } = require("../db");

async function stations() {
  return many(
    `SELECT ves.*, s.adresse, s.latitude, s.longitude, s.seuil_velos_min, s.seuil_bornes_min 
    FROM v_etat_station ves 
    JOIN station s 
    ON s.id_station = ves.id_station 
    ORDER BY ves.nom`,
  );
}
async function bikes() {
  return many(
    `SELECT v.numero_serie, v.etat, v.niveau_batterie, v.id_borne_actuelle, b.id_station, c.libelle AS categorie, s.nom AS station_nom 
    FROM velo v 
    LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle 
    JOIN categorie_velo c ON c.id_categorie = v.id_categorie 
    LEFT JOIN station s ON s.id_station = b.id_station 
    ORDER BY s.nom, b.id_borne, v.numero_serie`,
  );
}
async function freeBores() {
  return many(
    `SELECT b.id_borne, b.id_station, s.nom AS station_nom 
    FROM borne b JOIN station s ON s.id_station = b.id_station 
    LEFT JOIN velo v ON v.id_borne_actuelle = b.id_borne 
    WHERE b.etat = 'disponible' 
    AND v.numero_serie IS NULL 
    ORDER BY s.nom, b.id_borne`,
  );
}
module.exports = { stations, bikes, freeBores };
