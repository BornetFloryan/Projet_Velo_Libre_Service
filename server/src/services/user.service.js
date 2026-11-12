const repository = require("../repositories/user.repository");
async function dashboard(id) {
  return repository.dashboard(id);
}
async function trips(id) {
  return repository.trips(id);
}
async function account(id) {
  return repository.account(id);
}
async function action(actor, type, body) {
  return { actor, type, body };
}
module.exports = { dashboard, trips, account, action };
