<script setup>
import { useRouter } from 'vue-router';
import { useAppStore } from '../stores/app.store';

const router = useRouter();
const store = useAppStore();
const logout = async () => { await store.logout(); router.push('/connexion'); };
const reset = async () => { await store.resetDatabase(); router.push('/operateur'); };
</script>

<template>
  <div class="app-shell">
    <header>
      <RouterLink class="brand" :to="store.isOperator ? '/operateur' : '/usager'"><span>V</span> VéloLibre</RouterLink>
      <nav>
        <RouterLink :to="store.isOperator ? '/operateur' : '/usager'">Tableau de bord</RouterLink>
        <RouterLink to="/stations">Stations</RouterLink>
        <template v-if="!store.isOperator"><RouterLink to="/usager/trajets">Mes trajets</RouterLink><RouterLink to="/usager/compte">Mon compte</RouterLink></template>
        <template v-else><RouterLink to="/operateur/operations">Opérations</RouterLink><RouterLink to="/operateur/statistiques">Statistiques</RouterLink></template>
      </nav>
      <div class="user"><span>{{ store.actor?.name }}<small v-if="store.isOperator"> · {{ store.actor.role }}</small></span><button v-if="store.actor?.role==='administration'" class="danger-button" @click="reset">Réinitialiser la BDD</button><button class="logout" @click="logout">Déconnexion</button></div>
    </header>
    <main><div v-if="store.error" class="alert danger">{{ store.error }}</div><div v-if="store.success" class="alert success">{{ store.success }}</div><div v-if="store.loading" class="loading">Chargement…</div><slot /></main>
    <aside v-if="store.isOperator && store.data.overdueTrips?.length" class="global-warning" role="alert">
      <b>⚠ Vélo non restitué depuis plus de 24 h</b>
      <span>{{ store.data.overdueTrips.length }} trajet(s) à vérifier.</span>
      <RouterLink to="/operateur">Voir les détails</RouterLink>
    </aside>
  </div>
</template>
