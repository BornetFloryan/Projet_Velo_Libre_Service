const express = require("express");
const { many, value, pool } = require("../db");
const { requireOperator, businessMessage } = require("../utils/http");

const router = express.Router();
router.use(requireOperator());

router.get("/overview", async (req, res, next) => {
  try {
    const [availableBikes, openReports, openInterventions, recentReports] =
      await Promise.all([
        value("SELECT COUNT(*) FROM velo WHERE etat = 'disponible'"),
        value(
          "SELECT COUNT(*) FROM signalement s LEFT JOIN intervention i ON i.id_signalement = s.id_signalement WHERE i.id_intervention IS NULL",
        ),
        value("SELECT COUNT(*) FROM intervention WHERE date_fin IS NULL"),
        many(`SELECT s.*, st.nom AS station_nom, CONCAT(u.prenom, ' ', u.nom) AS usager_nom FROM signalement s
        JOIN station st ON st.id_station = s.id_station JOIN usager u ON u.id_usager = s.id_usager
        ORDER BY s.date_detection DESC LIMIT 8`),
      ]);
    res.json({ availableBikes, openReports, openInterventions, recentReports });
  } catch (e) {
    next(e);
  }
});

router.get("/operations", async (req, res, next) => {
  try {
    const [bikes, reports, interventions, stations, tours, moves, formulas] =
      await Promise.all([
        many(`SELECT v.*, c.libelle AS categorie, b.id_station, s.nom AS station_nom 
        FROM velo v 
        JOIN categorie_velo c ON c.id_categorie = v.id_categorie 
        LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle 
        LEFT JOIN station s ON s.id_station = b.id_station 
        ORDER BY v.numero_serie`),
        many(`SELECT s.id_signalement, s.date_detection, s.description, s.numero_serie, st.nom AS station_nom, CONCAT(u.prenom, ' ', u.nom) AS usager_nom, i.id_intervention 
        FROM signalement s 
        JOIN station st ON st.id_station = s.id_station 
        JOIN usager u ON u.id_usager = s.id_usager 
        JOIN velo v ON v.numero_serie = s.numero_serie 
        LEFT JOIN intervention i ON i.id_signalement = s.id_signalement 
        ORDER BY s.date_detection DESC`),
        many(`SELECT i.*, s.numero_serie, st.nom AS station_nom, o.nom_prenom 
        FROM intervention i 
        JOIN signalement s ON s.id_signalement = i.id_signalement 
        JOIN station st ON st.id_station = s.id_station 
        JOIN operateur o ON o.id_operateur = i.id_operateur 
        ORDER BY i.date_debut DESC`),
        many("SELECT * FROM station ORDER BY nom"),
        many(
          "SELECT t.*, o.nom_prenom FROM tournee_regulation t JOIN operateur o ON o.id_operateur = t.id_operateur ORDER BY t.date_tournee DESC, t.id_tournee DESC LIMIT 20",
        ),
        many(
          "SELECT m.*, s.nom AS station_nom, t.immatriculation_vehicule FROM mouvement_regulation m JOIN station s ON s.id_station = m.id_station JOIN tournee_regulation t ON t.id_tournee = m.id_tournee ORDER BY m.date_heure DESC LIMIT 30",
        ),
        many("SELECT * FROM formule ORDER BY nom"),
      ]);
    res.json({
      bikes,
      reports,
      interventions,
      stations,
      tours,
      moves,
      formulas,
    });
  } catch (e) {
    next(e);
  }
});

router.get("/statistics", async (req, res, next) => {
  try {
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
        `SELECT s.nom, COUNT(t.id_trajet) AS nombre_trajets, COALESCE(SUM(t.cout_facture), 0) AS recettes FROM station s LEFT JOIN trajet t ON t.id_station_depart = s.id_station AND t.date_arrivee IS NOT NULL GROUP BY s.id_station, s.nom ORDER BY nombre_trajets DESC, s.nom`,
      ),
      value(
        "SELECT COALESCE(SUM(cout_facture), 0) FROM trajet WHERE date_arrivee IS NOT NULL",
      ),
      value("SELECT COUNT(*) FROM trajet WHERE date_arrivee IS NOT NULL"),
      value(
        "SELECT COALESCE(AVG(duree_minutes), 0) FROM trajet WHERE date_arrivee IS NOT NULL",
      ),
    ]);
    res.json({
      dailyStats,
      formulaStats,
      topStations,
      totalRevenue,
      completedTrips,
      averageDuration,
    });
  } catch (e) {
    next(e);
  }
});

router.post("/actions/:action", async (req, res) => {
  const b = req.body;
  const actor = req.session.actor;
  try {
    switch (req.params.action) {
      case "update_bike":
        if (!["maintenance", "administration"].includes(actor.role))
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "UPDATE velo SET etat = :state, niveau_batterie = :battery, id_borne_actuelle = COALESCE(:borne, id_borne_actuelle) WHERE numero_serie = :serial",
          {
            state: b.etat,
            battery:
              b.niveau_batterie === "" ? null : Number(b.niveau_batterie),
            borne: b.id_borne_actuelle ? Number(b.id_borne_actuelle) : null,
            serial: b.numero_serie,
          },
        );
        break;
      case "create_intervention":
        if (!["maintenance", "administration"].includes(actor.role))
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "INSERT INTO intervention (date_debut, type_action, id_signalement, id_operateur) VALUES (NOW(), :action, :signal, :operator)",
          {
            action: b.type_action,
            signal: Number(b.id_signalement),
            operator: actor.id,
          },
        );
        break;
      case "finish_intervention":
        if (!["maintenance", "administration"].includes(actor.role))
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "UPDATE intervention SET date_fin = NOW() WHERE id_intervention = :id AND id_operateur = :operator AND date_fin IS NULL",
          { id: Number(b.id_intervention), operator: actor.id },
        );
        break;
      case "create_tournee":
        if (!["regulation", "administration"].includes(actor.role))
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "INSERT INTO tournee_regulation (date_tournee, immatriculation_vehicule, id_operateur) VALUES (:date, :vehicle, :operator)",
          {
            date: b.date_tournee,
            vehicle: b.immatriculation_vehicule,
            operator: actor.id,
          },
        );
        break;
      case "add_movement":
        if (!["regulation", "administration"].includes(actor.role))
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "INSERT INTO mouvement_regulation (date_heure, nature, id_tournee, numero_serie, id_station) VALUES (NOW(), :nature, :tournee, :serial, :station)",
          {
            nature: b.nature,
            tournee: Number(b.id_tournee),
            serial: b.numero_serie,
            station: Number(b.id_station),
          },
        );
        break;
      case "update_station":
        if (actor.role !== "administration")
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "UPDATE station SET etat = :state, seuil_velos_min = :bikes, seuil_bornes_min = :bores WHERE id_station = :station",
          {
            state: b.etat,
            bikes: Number(b.seuil_velos_min),
            bores: Number(b.seuil_bornes_min),
            station: Number(b.id_station),
          },
        );
        break;
      case "create_formula":
        if (actor.role !== "administration")
          throw new Error("Votre rôle ne permet pas cette action.");
        await pool.execute(
          "INSERT INTO formule (nom, prix_base, duree_gratuite_minutes, tarif_par_tranche, supplement_electrique) VALUES (:name, :price, :duration, :slice, :extra)",
          {
            name: b.nom,
            price: Number(b.prix_base),
            duration: Number(b.duree_gratuite_minutes),
            slice: Number(b.tarif_par_tranche),
            extra: Number(b.supplement_electrique),
          },
        );
        break;
      default:
        return res.status(404).json({ error: "Action inconnue." });
    }
    res.json({ message: "Opération effectuée." });
  } catch (e) {
    res.status(400).json({ error: businessMessage(e) });
  }
});

module.exports = router;
