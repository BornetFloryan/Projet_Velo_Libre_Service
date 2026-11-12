const express = require('express');
const controller = require('../controllers/network.controller');
const { requireActor } = require('../utils/http');

const router = express.Router();
router.use(requireActor);
router.get('/stations', controller.stations);
module.exports = router;
