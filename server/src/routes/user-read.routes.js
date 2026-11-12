const express = require("express");
const controller = require("../controllers/user.controller");
const { requireUser } = require("../utils/http");
const router = express.Router();
router.use(requireUser);
router.get("/dashboard", controller.dashboard);
router.get("/trips", controller.trips);
router.get("/account", controller.account);
module.exports = router;
