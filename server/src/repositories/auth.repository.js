const { one, pool } = require("../db");
async function userByEmail(email) {
  return one(
    'SELECT id_usager AS id, CONCAT(prenom, " ", nom) AS name, email, mot_de_passe_hash FROM usager WHERE email=:email',
    { email },
  );
}
async function operatorByEmail(email) {
  return one(
    "SELECT id_operateur AS id, nom_prenom AS name, email, mot_de_passe_hash, role_operateur AS role FROM operateur WHERE email=:email",
    { email },
  );
}
async function createUser(data) {
  return pool.execute(
    "INSERT INTO usager (nom, prenom, email, mot_de_passe_hash) VALUES (:nom,:prenom,:email,:hash)",
    data,
  );
}
module.exports = { userByEmail, operatorByEmail, createUser };
