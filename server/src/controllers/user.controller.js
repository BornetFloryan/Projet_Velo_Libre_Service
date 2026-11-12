const service = require("../services/user.service");
async function dashboard(req, res, next) {
  try {
    res.json(await service.dashboard(req.session.actor.id));
  } catch (e) {
    next(e);
  }
}
async function trips(req, res, next) {
  try {
    res.json(await service.trips(req.session.actor.id));
  } catch (e) {
    next(e);
  }
}
async function account(req, res, next) {
  try {
    res.json(await service.account(req.session.actor.id));
  } catch (e) {
    next(e);
  }
}
module.exports = { dashboard, trips, account };
