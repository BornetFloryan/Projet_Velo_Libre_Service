const path = require('node:path');
const express = require('express');
const session = require('express-session');
const config = require('./config');
const { businessMessage } = require('./http');

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(session({ secret: config.sessionSecret, resave: false, saveUninitialized: false, cookie: { httpOnly: true, sameSite: 'lax' } }));

app.use('/api/auth', require('./auth.routes'));
app.use('/api/network', require('./network.routes'));
app.use('/api/user', require('./user.routes'));
app.use('/api/operator', require('./operator.routes'));
app.get('/api/health', async (req, res) => { try { const { value } = require('./db'); await value('SELECT 1'); res.json({ ok: true }); } catch (e) { res.status(503).json({ ok: false, error: businessMessage(e) }); } });

const clientPath = path.resolve(__dirname, '../../client');
app.use(express.static(clientPath));
app.get('*', (req, res) => res.sendFile(path.join(clientPath, 'index.html')));
app.use((error, req, res, next) => res.status(500).json({ error: businessMessage(error) }));

if (require.main === module) app.listen(config.port, () => console.log(`VéloLibre Node/Vue disponible sur http://localhost:${config.port}`));
module.exports = app;
