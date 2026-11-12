const { defineConfig } = require('vite');
const path = require('node:path');

module.exports = defineConfig({
  root: path.resolve(__dirname, 'client'),
  build: { outDir: path.resolve(__dirname, 'client/dist'), emptyOutDir: true },
  server: { port: 5173, host: '127.0.0.1' }
});
