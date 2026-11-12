const service = require('../services/operator.service');
async function overview(req, res, next) { try { res.json(await service.overview()); } catch (e) { next(e); } }
async function statistics(req, res, next) { try { res.json(await service.statistics()); } catch (e) { next(e); } }
module.exports = { overview, statistics };
