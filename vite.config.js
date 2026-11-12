const { defineConfig } = require('vite');
const vue = require('@vitejs/plugin-vue');
const path = require('node:path');

module.exports = defineConfig({
  plugins: [vue()],
  root: path.resolve(__dirname, 'client'),
  build: { outDir: path.resolve(__dirname, 'client/dist'), emptyOutDir: true },
  server: { port: 5173, host: '127.0.0.1' }
});
