function businessMessage(error) {
  const message = String(error?.sqlMessage || error?.message || 'Une erreur est survenue.');
  if (/Duplicate entry/i.test(message)) return 'Cette information existe déjà dans la base.';
  if (/foreign key constraint fails/i.test(message)) return 'L’opération est impossible car une donnée associée est manquante ou encore utilisée.';
  return message.replace(/^Error: /, '');
}

function requireActor(req, res, next) {
  if (!req.session.actor) return res.status(401).json({ error: 'Veuillez vous connecter.' });
  next();
}

function requireUser(req, res, next) {
  if (!req.session.actor || req.session.actor.type !== 'usager') return res.status(403).json({ error: 'Cette action est réservée aux usagers.' });
  next();
}

function requireOperator(roles = []) {
  return (req, res, next) => {
    const actor = req.session.actor;
    if (!actor || actor.type !== 'operateur') return res.status(403).json({ error: 'Cette action est réservée aux opérateurs.' });
    if (roles.length && !roles.includes(actor.role)) return res.status(403).json({ error: 'Votre rôle ne permet pas cette action.' });
    next();
  };
}

module.exports = { businessMessage, requireActor, requireUser, requireOperator };
