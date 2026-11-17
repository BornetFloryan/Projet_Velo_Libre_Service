const { resetToDemoData } = require("../services/reset.service");

async function reset(req, res) {
  try {
    const mode = req.body?.mode || "standard";
    await resetToDemoData(undefined, mode);
    const messages = {
      standard: "La base a été remise dans son état initial de démonstration.",
      baseline: "La base de comparaison sans index composé est prête.",
      optimized: "La base de comparaison optimisée est prête.",
    };
    res.json({
      message: messages[mode],
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}

module.exports = { reset };
