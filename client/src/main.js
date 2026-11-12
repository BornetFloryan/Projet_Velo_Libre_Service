const { createApp, ref, computed, onMounted } = Vue;

const api = async (url, options = {}) => {
  const response = await fetch(`/api${url}`, { credentials: 'same-origin', headers: { 'Content-Type': 'application/json', ...(options.headers || {}) }, ...options });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(data.error || 'Une erreur est survenue.');
  return data;
};

createApp({
  setup() {
    const actor = ref(null); const view = ref('dashboard'); const loading = ref(false); const error = ref(''); const success = ref('');
    const loginMode = ref('login'); const login = ref({ email: '', password: '' }); const register = ref({ prenom: '', nom: '', email: '', password: '' });
    const data = ref({}); const form = ref({});
    const isOperator = computed(() => actor.value?.type === 'operateur');
    const title = computed(() => ({ dashboard: 'Tableau de bord', stations: 'Stations', trips: 'Mes trajets', account: 'Mon compte', operations: 'Opérations', statistics: 'Statistiques' }[view.value] || 'VéloLibre'));
    const resetMessage = () => { error.value = ''; success.value = ''; };
    const run = async (task) => { resetMessage(); loading.value = true; try { await task(); } catch (e) { error.value = e.message; } finally { loading.value = false; } };
    const load = async (next = view.value) => run(async () => { view.value = next; const routes = { dashboard: isOperator.value ? '/operator/overview' : '/user/dashboard', stations: '/network/stations', trips: '/user/trips', account: '/user/account', operations: '/operator/operations', statistics: '/operator/statistics' }; data.value = await api(routes[next]); });
    const submitLogin = () => run(async () => { const result = await api('/auth/login', { method: 'POST', body: JSON.stringify(login.value) }); actor.value = result.actor; await load('dashboard'); });
    const submitRegister = () => run(async () => { const result = await api('/auth/register', { method: 'POST', body: JSON.stringify(register.value) }); success.value = result.message; loginMode.value = 'login'; });
    const logout = () => run(async () => { await api('/auth/logout', { method: 'POST' }); actor.value = null; data.value = {}; });
    const action = (type, payload, reload = view.value) => run(async () => { const prefix = isOperator.value ? '/operator/actions/' : '/user/actions/'; const result = await api(prefix + type, { method: 'POST', body: JSON.stringify(payload) }); success.value = result.message; await load(reload); });
    const boot = async () => { try { const result = await api('/auth/me'); actor.value = result.actor; if (actor.value) await load('dashboard'); } catch (_) {} };
    onMounted(boot);

    return { actor, view, title, loading, error, success, loginMode, login, register, data, form, isOperator, submitLogin, submitRegister, logout, load, action };
  },
  template: `
  <div v-if="!actor" class="auth-shell">
    <div class="auth-card">
      <div class="brand"><span>V</span> VéloLibre</div>
      <p class="eyebrow">Projet BD70 · UTBM</p>
      <h1>{{ loginMode === 'login' ? 'Connexion' : 'Créer un compte' }}</h1>
      <div v-if="error" class="alert danger">{{ error }}</div><div v-if="success" class="alert success">{{ success }}</div>
      <form v-if="loginMode === 'login'" @submit.prevent="submitLogin">
        <label>E-mail<input v-model="login.email" type="email" required></label><label>Mot de passe<input v-model="login.password" type="password" required></label>
        <button class="primary" :disabled="loading">Se connecter</button>
      </form>
      <form v-else @submit.prevent="submitRegister">
        <div class="grid two"><label>Prénom<input v-model="register.prenom" required></label><label>Nom<input v-model="register.nom" required></label></div>
        <label>E-mail<input v-model="register.email" type="email" required></label><label>Mot de passe<input v-model="register.password" type="password" minlength="8" required></label>
        <button class="primary" :disabled="loading">Créer le compte</button>
      </form>
      <button class="link" @click="loginMode = loginMode === 'login' ? 'register' : 'login'; error = ''; success = ''">{{ loginMode === 'login' ? 'Créer un compte' : 'J’ai déjà un compte' }}</button>
    </div>
  </div>
  <div v-else class="app-shell">
    <header><div class="brand"><span>V</span> VéloLibre</div><nav>
      <button :class="{active:view==='dashboard'}" @click="load('dashboard')">Tableau de bord</button><button :class="{active:view==='stations'}" @click="load('stations')">Stations</button>
      <template v-if="!isOperator"><button :class="{active:view==='trips'}" @click="load('trips')">Mes trajets</button><button :class="{active:view==='account'}" @click="load('account')">Mon compte</button></template>
      <template v-else><button :class="{active:view==='operations'}" @click="load('operations')">Opérations</button><button :class="{active:view==='statistics'}" @click="load('statistics')">Statistiques</button></template>
    </nav><div class="user">{{ actor.name }} <small v-if="isOperator">· {{ actor.role }}</small><button class="logout" @click="logout">Déconnexion</button></div></header>
    <main><div class="page-head"><div><p class="eyebrow">VéloLibre · espace {{ isOperator ? 'opérateur' : 'usager' }}</p><h1>{{ title }}</h1></div><button class="secondary" @click="load(view)">Actualiser</button></div>
      <div v-if="error" class="alert danger">{{ error }}</div><div v-if="success" class="alert success">{{ success }}</div><div v-if="loading" class="loading">Chargement…</div>
      <section v-if="view==='dashboard' && !isOperator" class="stack"><div class="grid metrics"><article class="metric"><span>Solde</span><strong>{{ data.user?.solde_portefeuille ?? 0 }} €</strong></article><article class="metric"><span>Abonnement</span><strong>{{ data.subscription?.formule_nom || 'Aucun' }}</strong></article><article class="metric"><span>Réservation</span><strong>{{ data.reservation ? data.reservation.numero_serie : 'Aucune' }}</strong></article></div><div class="grid two"><article class="card"><h2>Trajet en cours</h2><p v-if="data.openTrip">Vélo {{ data.openTrip.numero_serie }} · départ {{ data.openTrip.station_depart_nom }}</p><p v-else class="muted">Aucun trajet en cours.</p><button v-if="data.openTrip" class="primary" @click="load('trips')">Restituer le vélo</button></article><article class="card"><h2>Réservation active</h2><p v-if="data.reservation">Vélo {{ data.reservation.numero_serie }}<span v-if="data.reservation.station_nom"> · {{ data.reservation.station_nom }}</span></p><p v-else class="muted">Aucune réservation active.</p></article></div></section>
      <section v-if="view==='dashboard' && isOperator" class="grid metrics"><article class="metric"><span>Vélos disponibles</span><strong>{{ data.availableBikes }}</strong></article><article class="metric"><span>Signalements ouverts</span><strong>{{ data.openReports }}</strong></article><article class="metric"><span>Interventions ouvertes</span><strong>{{ data.openInterventions }}</strong></article></section>
      <section v-if="view==='stations'" class="stack"><article v-for="s in data.stations" :key="s.id_station" class="card station"><div><h2>{{ s.nom }}</h2><p class="muted">{{ s.adresse }} · {{ s.etat }}</p></div><div class="station-stats"><b>{{ s.velos_disponibles }}</b> vélos · <b>{{ s.bornes_libres }}</b> bornes libres</div><div class="bike-list"><span v-for="bike in data.bikes?.filter(b => b.id_station === s.id_station)" :key="bike.numero_serie" class="tag">{{ bike.numero_serie }} · {{ bike.categorie }} <button v-if="!isOperator && bike.etat === 'disponible'" @click="action('reserve_bike',{numero_serie:bike.numero_serie},'stations')">Réserver</button></span></div></article></section>
      <section v-if="view==='trips'" class="stack"><article v-if="data.openTrip" class="card"><h2>Restituer le trajet {{ data.openTrip.id_trajet }}</h2><div class="grid two"><label>Station d’arrivée<select v-model="form.id_station_arrivee"><option v-for="s in (data.stations || [])" :value="s.id_station">{{ s.nom }}</option></select></label><label>Borne d’arrivée<input v-model="form.id_borne_arrivee" type="number" required></label></div><button class="primary" @click="action('return_trip',{...form,id_trajet:data.openTrip.id_trajet},'trips')">Restituer</button></article><article class="card"><h2>Historique</h2><table><tr><th>Date</th><th>Vélo</th><th>Départ</th><th>Arrivée</th><th>Coût</th><th>Note</th></tr><tr v-for="t in data.trips" :key="t.id_trajet"><td>{{ t.date_depart }}</td><td>{{ t.numero_serie }}</td><td>{{ t.station_depart_nom }}</td><td>{{ t.station_arrivee_nom || 'En cours' }}</td><td>{{ t.cout_facture }} €</td><td><button v-if="t.date_arrivee && !t.note_evaluation" class="link" @click="action('rate_trip',{id_trajet:t.id_trajet,note_evaluation:5},'trips')">Noter 5/5</button><span v-else>{{ t.note_evaluation || '—' }}</span></td></tr></table></article></section>
      <section v-if="view==='account'" class="grid two"><article class="card"><h2>Profil</h2><div class="grid two"><label>Prénom<input v-model="data.user.prenom"></label><label>Nom<input v-model="data.user.nom"></label></div><label>E-mail<input v-model="data.user.email" type="email"></label><button class="primary" @click="action('update_profile',data.user,'account')">Enregistrer</button></article><article class="card"><h2>Portefeuille</h2><p class="balance">{{ data.user?.solde_portefeuille }} €</p><label>Montant<input v-model="form.montant" type="number" min="1" step="0.01"></label><button class="primary" @click="action('recharge_wallet',form,'account')">Recharger</button></article><article class="card full"><h2>Formules</h2><div class="cards"><div v-for="f in data.formulas" :key="f.id_formule" class="formula"><b>{{ f.nom }}</b><span>{{ f.prix_base }} €</span><button class="secondary" @click="action('buy_subscription',{id_formule:f.id_formule},'account')">Souscrire</button></div></div></article></section>
      <section v-if="view==='operations'" class="stack"><article class="card"><h2>Flotte</h2><table><tr><th>Vélo</th><th>Catégorie</th><th>État</th><th>Station</th><th>Action</th></tr><tr v-for="b in data.bikes" :key="b.numero_serie"><td>{{ b.numero_serie }}</td><td>{{ b.categorie }}</td><td><select v-model="b.etat"><option v-for="s in ['disponible','reserve','en_cours','maintenance','atelier','hors_service']" :value="s">{{ s }}</option></select></td><td>{{ b.station_nom || '—' }}</td><td><button class="secondary" @click="action('update_bike',{numero_serie:b.numero_serie,etat:b.etat,niveau_batterie:b.niveau_batterie},'operations')">Enregistrer</button></td></tr></table></article><article class="card"><h2>Signalements et interventions</h2><div v-for="r in data.reports" :key="r.id_signalement" class="row"><span><b>{{ r.numero_serie }}</b> · {{ r.station_nom }} · {{ r.description }}</span><button v-if="!r.id_intervention" class="secondary" @click="action('create_intervention',{id_signalement:r.id_signalement,type_action:'Diagnostic'},'operations')">Ouvrir intervention</button></div></article></section>
      <section v-if="view==='statistics'" class="grid metrics"><article class="metric"><span>Recettes totales</span><strong>{{ data.totalRevenue }} €</strong></article><article class="metric"><span>Trajets terminés</span><strong>{{ data.completedTrips }}</strong></article><article class="metric"><span>Durée moyenne</span><strong>{{ Number(data.averageDuration || 0).toFixed(0) }} min</strong></article><article class="card full"><h2>Stations les plus utilisées</h2><table><tr><th>Station</th><th>Trajets</th><th>Recettes</th></tr><tr v-for="s in data.topStations" :key="s.nom"><td>{{ s.nom }}</td><td>{{ s.nombre_trajets }}</td><td>{{ s.recettes }} €</td></tr></table></article></section>
    </main>
  </div>`
}).mount('#app');
