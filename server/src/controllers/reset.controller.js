const { resetToDemoData } = require("../services/reset.service");

async function reset(req, res) {
  try {
    await resetToDemoData();
    res.json({
      message: "La base a été remise dans son état initial de démonstration.",
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

module.exports = { reset };
