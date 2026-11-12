const bcrypt = require("bcryptjs");
const repository = require("../repositories/auth.repository");
async function login(email, password) {
  const user = await repository.userByEmail(email);
  if (user && (await bcrypt.compare(password, user.mot_de_passe_hash)))
    return { type: "usager", id: user.id, name: user.name, email: user.email };
  const operator = await repository.operatorByEmail(email);
  if (operator && (await bcrypt.compare(password, operator.mot_de_passe_hash)))
    return {
      type: "operateur",
      id: operator.id,
      name: operator.name,
      email: operator.email,
      role: operator.role,
    };
  return null;
}
async function register(data) {
  if (
    !data.prenom ||
    !data.nom ||
    !data.email ||
    String(data.password || "").length < 8
  )
    throw new Error(
      "Renseignez tous les champs et utilisez un mot de passe de 8 caractères minimum.",
    );
  return repository.createUser({
    nom: data.nom,
    prenom: data.prenom,
    email: data.email,
    hash: await bcrypt.hash(data.password, 10),
  });
}
module.exports = { login, register };
