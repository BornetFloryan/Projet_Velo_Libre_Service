import AppShell from './components/app-shell.js';
import AuthView from './views/auth.view.js';
import { UserDashboardView, StationsView, TripsView, AccountView } from './views/user.views.js';
import { OperatorDashboardView, OperationsView, StatisticsView } from './views/operator.views.js';
import { useAppStore } from './stores/app.store.js';

const { createApp, onMounted } = window.Vue;

const app = createApp({
  components: { AppShell, AuthView, UserDashboardView, StationsView, TripsView, AccountView, OperatorDashboardView, OperationsView, StatisticsView },
  setup() {
    const store = useAppStore();
    onMounted(() => store.boot());
    return { store };
  },
  template: `<AuthView v-if="!store.actor" :mode="store.authMode" :login="store.login" :register="store.register" :loading="store.loading" :error="store.error" :success="store.success" @login="store.loginUser" @register="store.registerUser" @toggle="store.authMode=store.authMode==='login'?'register':'login'"/><AppShell v-else :actor="store.actor" :view="store.view" :is-operator="store.isOperator" :title="store.title" :loading="store.loading" :error="store.error" :success="store.success" @navigate="store.load" @logout="store.logout" @reset="store.resetDatabase"><UserDashboardView v-if="store.view==='dashboard'&&!store.isOperator" :data="store.data" @navigate="store.load"/><OperatorDashboardView v-else-if="store.view==='dashboard'" :data="store.data"/><StationsView v-else-if="store.view==='stations'" :data="store.data" :is-operator="store.isOperator" @action="store.action"/><TripsView v-else-if="store.view==='trips'" :data="store.data" :form="store.form" @action="store.action"/><AccountView v-else-if="store.view==='account'" :data="store.data" :form="store.form" @action="store.action"/><OperationsView v-else-if="store.view==='operations'" :data="store.data" :actor="store.actor" @action="store.action"/><StatisticsView v-else-if="store.view==='statistics'" :data="store.data"/></AppShell>`
});

app.use(window.Pinia.createPinia());
app.mount('#app');
