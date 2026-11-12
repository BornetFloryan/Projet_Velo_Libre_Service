const service = require('../services/network.service');
async function stations(req, res, next) { try { res.json(await service.getStations()); } catch (error) { next(error); } }
module.exports = { stations };
