const path = require('node:path');
const express = require('express');
const session = require('express-session');
const config = require('./config');
const { businessMessage } = require('./utils/http');

const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(session({ secret: config.sessionSecret, resave: false, saveUninitialized: false, cookie: { httpOnly: true, sameSite: 'lax' } }));

app.use('/api/auth', require('./routes/auth-clean.routes'));
app.use('/api/auth', require('./routes/auth.routes'));
app.use('/api/network', require('./routes/network.routes'));
app.use('/api/user', require('./routes/user-read.routes'));
app.use('/api/user', require('./routes/user.routes'));
app.use('/api/operator', require('./routes/operator-read.routes'));
app.use('/api/operator', require('./routes/operator.routes'));
app.use('/api/common', require('./routes/common.routes'));

const clientPath = path.resolve(__dirname, '../../client/dist');
app.use(express.static(clientPath));
app.get('*', (req, res) => res.sendFile(path.join(clientPath, 'index.html')));
app.use((error, req, res, next) => res.status(500).json({ error: businessMessage(error) }));

if (require.main === module) {
  const start = (port) => {
    const server = app.listen(port, () => console.log(`VéloLibre Node/Vue disponible sur http://localhost:${port}`));
    server.on('error', (error) => {
      if (error.code === 'EADDRINUSE' && port < config.port + 10) {
        console.warn(`Le port ${port} est déjà utilisé. Essai sur le port ${port + 1}…`);
        start(port + 1);
        return;
      }
      throw error;
    });
  };
  start(config.port);
}
module.exports = app;
