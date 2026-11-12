const repository = require('../repositories/network.repository');
async function getStations() { const [stations, bikes, freeBores] = await Promise.all([repository.stations(), repository.bikes(), repository.freeBores()]); return { stations, bikes, freeBores }; }
module.exports = { getStations };
