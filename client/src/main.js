import AppShell from './components/app-shell.js';
import AuthView from './views/auth.view.js';
import { UserDashboardView, StationsView, TripsView, AccountView } from './views/user.views.js';
import { OperatorDashboardView, OperationsView, StatisticsView } from './views/operator.views.js';

const { createApp, ref, computed, onMounted } = window.Vue;

const api = async (url, options = {}) => {
  const response = await fetch(`/api${url}`, { credentials: 'same-origin', headers: { 'Content-Type': 'application/json', ...(options.headers || {}) }, ...options });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || 'Une erreur est survenue.');
  return data;
};

const app = createApp({
  components: { AppShell, AuthView, UserDashboardView, StationsView, TripsView, AccountView, OperatorDashboardView, OperationsView, StatisticsView },
  setup() {
    const actor = ref(null); const view = ref('dashboard'); const data = ref({}); const form = ref({});
    const loading = ref(false); const error = ref(''); const success = ref(''); const authMode = ref('login');
    const login = ref({ email: '', password: '' }); const register = ref({ prenom: '', nom: '', email: '', password: '' });
    const isOperator = computed(() => actor.value?.type === 'operateur');
    const title = computed(() => ({ dashboard: 'Tableau de bord', stations: 'Stations', trips: 'Mes trajets', account: 'Mon compte', operations: 'Opérations', statistics: 'Statistiques' }[view.value] || 'VéloLibre'));
    const run = async task => { error.value = ''; success.value = ''; loading.value = true; try { await task(); } catch (e) { error.value = e.message; } finally { loading.value = false; } };
    const load = next => run(async () => { view.value = next; const route = { dashboard: isOperator.value ? '/operator/overview' : '/user/dashboard', stations: '/network/stations', trips: '/user/trips', account: '/user/account', operations: '/operator/operations', statistics: '/operator/statistics' }[next]; data.value = await api(route); data.value.form ||= form.value; });
    const loginUser = () => run(async () => { const result = await api('/auth/login', { method: 'POST', body: JSON.stringify(login.value) }); actor.value = result.actor; await load('dashboard'); });
    const registerUser = () => run(async () => { const result = await api('/auth/register', { method: 'POST', body: JSON.stringify(register.value) }); success.value = result.message; authMode.value = 'login'; });
    const logout = () => run(async () => { await api('/auth/logout', { method: 'POST' }); actor.value = null; data.value = {}; });
    const action = (type, payload, next = view.value) => run(async () => { const prefix = isOperator.value ? '/operator/actions/' : '/user/actions/'; const result = await api(prefix + type, { method: 'POST', body: JSON.stringify(payload) }); success.value = result.message; await load(next); });
    const resetDatabase = () => run(async () => { if (!window.confirm('Remettre la base dans son état initial ? Toutes les données actuelles seront supprimées.')) return; const result = await api('/common/reset-database', { method: 'POST' }); success.value = result.message; await load('dashboard'); });
    onMounted(async () => { try { const result = await api('/auth/me'); actor.value = result.actor; if (actor.value) await load('dashboard'); } catch (_) {} });
    return { actor, view, data, form, loading, error, success, authMode, login, register, isOperator, title, load, loginUser, registerUser, logout, action, resetDatabase };
  },
  template: `<AuthView v-if="!actor" :mode="authMode" :login="login" :register="register" :loading="loading" :error="error" :success="success" @login="loginUser" @register="registerUser" @toggle="authMode=authMode==='login'?'register':'login'"/><AppShell v-else :actor="actor" :view="view" :is-operator="isOperator" :title="title" :loading="loading" :error="error" :success="success" @navigate="load" @logout="logout" @reset="resetDatabase"><UserDashboardView v-if="view==='dashboard'&&!isOperator" :data="data" @navigate="load"/><OperatorDashboardView v-else-if="view==='dashboard'" :data="data"/><StationsView v-else-if="view==='stations'" :data="data" :is-operator="isOperator" @action="action"/><TripsView v-else-if="view==='trips'" :data="data" :form="form" @action="action"/><AccountView v-else-if="view==='account'" :data="data" :form="form" @action="action"/><OperationsView v-else-if="view==='operations'" :data="data" :actor="actor" @action="action"/><StatisticsView v-else-if="view==='statistics'" :data="data"/></AppShell>`
});

app.mount('#app');
