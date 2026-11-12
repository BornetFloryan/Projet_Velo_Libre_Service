const repository = require("../repositories/operator.repository");
module.exports = {
  overview: repository.overview,
  statistics: repository.statistics,
  history: repository.history,
};
