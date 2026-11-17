const fs = require("node:fs/promises");
const path = require("node:path");
const { spawn } = require("node:child_process");
const config = require("../config");

function mysqlBinary() {
  return (
    process.env.MYSQL_BIN ||
    (process.platform === "win32"
      ? "C:\\xampp\\mysql\\bin\\mysql.exe"
      : "mysql")
  );
}

function connectionArgs() {
  const args = [
    "--protocol=tcp",
    "--default-character-set=utf8mb4",
    "-h",
    config.database.host,
    "-P",
    String(config.database.port),
    "-u",
    config.database.user,
  ];
  if (config.database.password) args.push(`-p${config.database.password}`);
  return args;
}

function runMysql(input, extraArgs = []) {
  return new Promise((resolve, reject) => {
    const child = spawn(mysqlBinary(), [...connectionArgs(), ...extraArgs], {
      windowsHide: true,
    });
    let stderr = "";
    child.stderr.on("data", (chunk) => {
      stderr += chunk.toString();
    });
    child.on("error", reject);
    child.on("close", (code) =>
      code === 0
        ? resolve()
        : reject(new Error(stderr || `mysql a retourné le code ${code}`)),
    );
    child.stdin.end(input);
  });
}

async function resetToDemoData(
  targetDatabase = config.database.database,
  mode = "standard",
) {
  let schema = await fs.readFile(
    path.resolve(__dirname, "../../../velo_libre_service_mysql.sql"),
    "utf8",
  );
  let demoData = await fs.readFile(
    path.resolve(__dirname, "../../../donnees_test.sql"),
    "utf8",
  );
  const database = targetDatabase.replace(/`/g, "");
  schema = schema.replace(
    /USE\s+velo_libre_service\s*;/i,
    `USE \`${database}\`;`,
  );
  demoData = demoData.replace(
    /USE\s+velo_libre_service\s*;/i,
    `USE \`${database}\`;`,
  );

  if (!["standard", "baseline", "optimized"].includes(mode)) {
    throw new Error("Mode de base inconnu.");
  }

  await runMysql(
    `DROP DATABASE IF EXISTS \`${database}\`;\nCREATE DATABASE \`${database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n`,
  );
  await runMysql(schema);

  if (mode === "baseline") {
    await runMysql(`
      USE \`${database}\`;
      CREATE INDEX idx_historique_position_velo_simple
      ON historique_position (numero_serie);
      DROP INDEX idx_historique_position_velo_date ON historique_position;
    `);
  }

  await runMysql(demoData);

  if (mode === "baseline" || mode === "optimized") {
    let loadData = await fs.readFile(
      path.resolve(__dirname, "../../../donnees_charge_optimisation.sql"),
      "utf8",
    );
    loadData = loadData.replace(
      /USE\s+velo_libre_service\s*;/i,
      `USE \`${database}\`;`,
    );
    await runMysql(loadData);
  }
}

module.exports = { resetToDemoData };
