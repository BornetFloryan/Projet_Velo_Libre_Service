const express = require('express');
const { one, many, value, pool } = require('./db');
const { requireUser, businessMessage } = require('./http');

const router = express.Router();
router.use(requireUser);

async function userPayload(id) {
  const user = await one('SELECT * FROM usager WHERE id_usager = :id', { id });
  const openTrip = await one(`SELECT t.*, s.nom AS station_depart_nom, b.id_borne AS borne_depart FROM trajet t
    JOIN station s ON s.id_station = t.id_station_depart JOIN borne b ON b.id_borne = t.id_borne_depart
    WHERE t.id_usager = :id AND t.date_arrivee IS NULL`, { id });
  const reservation = await one(`SELECT r.*, b.id_borne, s.nom AS station_nom FROM reservation r
    LEFT JOIN velo v ON v.numero_serie = r.numero_serie LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle
    LEFT JOIN station s ON s.id_station = b.id_station WHERE r.id_usager = :id AND r.statut = 'active'`, { id });
  const subscription = await one(`SELECT a.*, f.nom AS formule_nom, f.duree_gratuite_minutes FROM abonnement a
    JOIN formule f ON f.id_formule = a.id_formule WHERE a.id_usager = :id ORDER BY a.date_fin DESC LIMIT 1`, { id });
  return { user, openTrip, reservation, subscription };
}

router.get('/dashboard', async (req, res, next) => { try { res.json(await userPayload(req.session.actor.id)); } catch (e) { next(e); } });

router.get('/trips', async (req, res, next) => {
  try {
    const openTrip = await one(`SELECT t.*, ds.nom AS station_depart_nom, db.id_borne AS borne_depart FROM trajet t
      JOIN station ds ON ds.id_station = t.id_station_depart JOIN borne db ON db.id_borne = t.id_borne_depart
      WHERE t.id_usager = :id AND t.date_arrivee IS NULL`, { id: req.session.actor.id });
    const reservation = await one(`SELECT r.*, s.nom AS station_nom, b.id_borne FROM reservation r
      LEFT JOIN velo v ON v.numero_serie = r.numero_serie LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle
      LEFT JOIN station s ON s.id_station = b.id_station WHERE r.id_usager = :id ORDER BY r.date_reservation DESC LIMIT 1`, { id: req.session.actor.id });
    const trips = await many(`SELECT t.*, ds.nom AS station_depart_nom, ar.nom AS station_arrivee_nom FROM trajet t
      JOIN station ds ON ds.id_station = t.id_station_depart LEFT JOIN station ar ON ar.id_station = t.id_station_arrivee
      WHERE t.id_usager = :id ORDER BY t.date_depart DESC`, { id: req.session.actor.id });
    res.json({ openTrip, reservation, trips });
  } catch (e) { next(e); }
});

router.get('/account', async (req, res, next) => {
  try {
    const id = req.session.actor.id;
    const payload = await userPayload(id);
    payload.subscriptions = await many(`SELECT a.*, f.nom AS formule_nom, f.prix_base, f.duree_gratuite_minutes, f.tarif_par_tranche, f.supplement_electrique
      FROM abonnement a JOIN formule f ON f.id_formule = a.id_formule WHERE a.id_usager = :id ORDER BY a.date_debut DESC`, { id });
    payload.operations = await many(`SELECT o.*, t.numero_serie FROM operation_portefeuille o LEFT JOIN trajet t ON t.id_trajet = o.id_trajet
      WHERE o.id_usager = :id ORDER BY o.date_heure DESC LIMIT 20`, { id });
    payload.formulas = await many('SELECT * FROM formule ORDER BY prix_base, nom');
    res.json(payload);
  } catch (e) { next(e); }
});

router.post('/actions/:action', async (req, res) => {
  const id = req.session.actor.id;
  const b = req.body;
  try {
    let message = 'Opération effectuée.';
    switch (req.params.action) {
      case 'reserve_bike': await pool.execute('INSERT INTO reservation (id_usager, numero_serie) VALUES (:id, :serial)', { id, serial: b.numero_serie }); message = 'Vélo réservé pendant 15 minutes.'; break;
      case 'cancel_reservation': { const [r] = await pool.execute("UPDATE reservation SET statut = 'annulee' WHERE id_reservation = :reservation AND id_usager = :id AND statut = 'active'", { reservation: Number(b.id_reservation), id }); if (!r.affectedRows) throw new Error('Cette réservation ne peut plus être annulée.'); message = 'Réservation annulée.'; break; }
      case 'start_trip': await pool.execute('INSERT INTO trajet (date_depart, id_usager, numero_serie, id_station_depart, id_borne_depart) VALUES (NOW(), :id, :serial, :station, :borne)', { id, serial: b.numero_serie, station: Number(b.id_station_depart), borne: Number(b.id_borne_depart) }); message = 'Trajet démarré. Bonne route !'; break;
      case 'return_trip': { const [r] = await pool.execute('UPDATE trajet SET date_arrivee = NOW(), id_station_arrivee = :station, id_borne_arrivee = :borne WHERE id_trajet = :trip AND id_usager = :id AND date_arrivee IS NULL', { station: Number(b.id_station_arrivee), borne: Number(b.id_borne_arrivee), trip: Number(b.id_trajet), id }); if (!r.affectedRows) throw new Error('Ce trajet ne peut plus être restitué.'); message = 'Vélo restitué. Le coût a été calculé automatiquement.'; break; }
      case 'rate_trip': { const [r] = await pool.execute('UPDATE trajet SET note_evaluation = :rating WHERE id_trajet = :trip AND id_usager = :id AND date_arrivee IS NOT NULL', { rating: Number(b.note_evaluation), trip: Number(b.id_trajet), id }); if (!r.affectedRows) throw new Error('Ce trajet ne peut pas être évalué.'); message = 'Merci pour votre évaluation.'; break; }
      case 'recharge_wallet': { const amount = Number(b.montant); const conn = await pool.getConnection(); try { await conn.beginTransaction(); await conn.execute('UPDATE usager SET solde_portefeuille = solde_portefeuille + :amount WHERE id_usager = :id', { amount, id }); await conn.execute("INSERT INTO operation_portefeuille (nature, montant, id_usager) VALUES ('rechargement', :amount, :id)", { amount, id }); await conn.commit(); } catch (e) { await conn.rollback(); throw e; } finally { conn.release(); } message = 'Portefeuille rechargé.'; break; }
      case 'buy_subscription': { const formula = await one('SELECT id_formule, nom FROM formule WHERE id_formule = :formula', { formula: Number(b.id_formule) }); if (!formula) throw new Error('Cette formule n’existe plus.'); const latest = await value('SELECT MAX(date_fin) FROM abonnement WHERE id_usager = :id AND date_fin >= CURDATE()', { id }); const start = latest ? new Date(new Date(latest).getTime() + 86400000) : new Date(); const duration = /annuel/i.test(formula.nom) ? 365 : (/mensuel/i.test(formula.nom) ? 30 : 1); const end = new Date(start.getTime() + (duration - 1) * 86400000); const fmt = d => d.toISOString().slice(0, 10); await pool.execute('INSERT INTO abonnement (date_debut, date_fin, id_usager, id_formule) VALUES (:start, :end, :id, :formula)', { start: fmt(start), end: fmt(end), id, formula: formula.id_formule }); message = `Abonnement « ${formula.nom} » enregistré.`; break; }
      case 'update_profile': await pool.execute('UPDATE usager SET prenom = :prenom, nom = :nom, email = :email WHERE id_usager = :id', { prenom: b.prenom, nom: b.nom, email: b.email, id }); req.session.actor.name = `${b.prenom} ${b.nom}`; req.session.actor.email = b.email; message = 'Profil mis à jour.'; break;
      case 'report_issue': await pool.execute('INSERT INTO signalement (description, id_usager, numero_serie, id_station) VALUES (:description, :id, :serial, :station)', { description: b.description, id, serial: b.numero_serie, station: Number(b.id_station) }); message = 'Signalement envoyé à l’équipe de maintenance.'; break;
      default: return res.status(404).json({ error: 'Action inconnue.' });
    }
    res.json({ message });
  } catch (e) { res.status(400).json({ error: businessMessage(e) }); }
});

module.exports = router;
