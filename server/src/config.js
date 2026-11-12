const path = require("node:path");
require("dotenv").config({ path: path.resolve(__dirname, "../../../.env") });

module.exports = {
  port: Number(process.env.PORT || 8080),
  sessionSecret: process.env.SESSION_SECRET || "velo-libre-service-development",
  database: {
    host: process.env.DB_HOST || "127.0.0.1",
    port: Number(process.env.DB_PORT || 3306),
    database: process.env.DB_NAME || "velo_libre_service",
    user: process.env.DB_USER || "root",
    password: process.env.DB_PASSWORD || "",
  },
};
