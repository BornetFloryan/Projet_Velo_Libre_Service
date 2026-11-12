const express = require('express');
const { value } = require('../repositories/sql.repository');
const { requireOperator } = require('../utils/http');
const { reset } = require('../controllers/reset.controller');

const router = express.Router();

router.get('/health', async (req, res) => {
  try { await value('SELECT 1'); res.json({ ok: true }); }
  catch (error) { res.status(503).json({ ok: false, error: error.message }); }
});

router.post('/reset-database', requireOperator(['administration']), reset);

module.exports = router;
