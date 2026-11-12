const mysql = require("mysql2/promise");
const config = require("./config");

const pool = mysql.createPool({
  ...config.database,
  waitForConnections: true,
  connectionLimit: 10,
  charset: "utf8mb4",
  namedPlaceholders: true,
});

async function one(sql, params = {}) {
  const [rows] = await pool.execute(sql, params);
  return rows[0] || null;
}

async function many(sql, params = {}) {
  const [rows] = await pool.execute(sql, params);
  return rows;
}

async function value(sql, params = {}) {
  const row = await one(sql, params);
  return row ? Object.values(row)[0] : null;
}

module.exports = { pool, one, many, value };
