const express = require('express');
const controller = require('../controllers/operator.controller');
const { requireOperator } = require('../utils/http');
const router = express.Router();
router.use(requireOperator());
router.get('/overview', controller.overview);
router.get('/statistics', controller.statistics);
module.exports = router;
