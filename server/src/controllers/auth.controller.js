const service = require("../services/auth.service");
async function login(req, res, next) {
  try {
    const actor = await service.login(
      String(req.body.email || "").trim(),
      String(req.body.password || ""),
    );
    if (!actor)
      return res
        .status(401)
        .json({ error: "Adresse e-mail ou mot de passe incorrect." });
    req.session.actor = actor;
    res.json({ actor });
  } catch (e) {
    next(e);
  }
}
async function register(req, res, next) {
  try {
    await service.register(req.body);
    res
      .status(201)
      .json({ message: "Compte créé. Vous pouvez maintenant vous connecter." });
  } catch (e) {
    next(e);
  }
}
module.exports = { login, register };
