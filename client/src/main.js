import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { formatDate, formatDateTime } from './utils/date.js';

const app = createApp(App);
app.config.globalProperties.$formatDate = formatDate;
app.config.globalProperties.$formatDateTime = formatDateTime;
app.use(createPinia()).use(router).mount('#app');
