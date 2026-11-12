const express = require('express');
const bcrypt = require('bcryptjs');
const { one } = require('./db');
const { businessMessage } = require('./http');

const router = express.Router();

router.get('/me', (req, res) => res.json({ actor: req.session.actor || null }));

router.post('/login', async (req, res) => {
  try {
    const email = String(req.body.email || '').trim();
    const password = String(req.body.password || '');
    const user = await one('SELECT id_usager AS id, CONCAT(prenom, " ", nom) AS name, email, mot_de_passe_hash FROM usager WHERE email = :email', { email });
    if (user && await bcrypt.compare(password, user.mot_de_passe_hash)) {
      req.session.actor = { type: 'usager', id: user.id, name: user.name, email: user.email };
      return res.json({ actor: req.session.actor });
    }
    const operator = await one('SELECT id_operateur AS id, nom_prenom AS name, email, mot_de_passe_hash, role_operateur AS role FROM operateur WHERE email = :email', { email });
    if (operator && await bcrypt.compare(password, operator.mot_de_passe_hash)) {
      req.session.actor = { type: 'operateur', id: operator.id, name: operator.name, email: operator.email, role: operator.role };
      return res.json({ actor: req.session.actor });
    }
    return res.status(401).json({ error: 'Adresse e-mail ou mot de passe incorrect.' });
  } catch (error) { return res.status(500).json({ error: businessMessage(error) }); }
});

router.post('/register', async (req, res) => {
  try {
    const { prenom, nom, email, password } = req.body;
    if (!prenom || !nom || !email || String(password || '').length < 8) return res.status(400).json({ error: 'Renseignez tous les champs et utilisez un mot de passe de 8 caractères minimum.' });
    await require('../db').pool.execute('INSERT INTO usager (nom, prenom, email, mot_de_passe_hash) VALUES (:nom, :prenom, :email, :hash)', { nom, prenom, email, hash: await bcrypt.hash(password, 10) });
    res.status(201).json({ message: 'Compte créé. Vous pouvez maintenant vous connecter.' });
  } catch (error) { res.status(400).json({ error: businessMessage(error) }); }
});

router.post('/logout', (req, res) => req.session.destroy(() => res.json({ ok: true })));

module.exports = router;
