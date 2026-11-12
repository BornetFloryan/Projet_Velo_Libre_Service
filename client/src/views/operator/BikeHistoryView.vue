<script setup>
import { onMounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAppStore } from '../../stores/app.store';

const store = useAppStore();
onMounted(() => store.load('history'));
const selectBike = () => store.load('history');
</script>

<template>
  <AppLayout>
    <section class="stack">
      <div class="intro"><p class="eyebrow">Suivi de flotte</p><h2>Historique des vélos</h2><p>Consultez les changements d’état et les déplacements enregistrés automatiquement.</p></div>
      <article class="card history-picker"><label>Vélo<select v-model="store.form.history_serial" @change="selectBike"><option v-for="bike in store.data.bikes" :key="bike.numero_serie" :value="bike.numero_serie">{{ bike.numero_serie }}</option></select></label></article>
      <div class="grid two"><article class="card table-card"><header><p class="eyebrow">Position</p><h2>Déplacements</h2></header><table><tr><th>Date</th><th>Action</th><th>De</th><th>Vers</th></tr><tr v-for="position in store.data.positions" :key="`${position.date_heure}-${position.type_deplacement}`"><td>{{ $formatDateTime(position.date_heure) }}</td><td>{{ position.type_deplacement }}</td><td>{{ position.ancienne_station || '—' }}</td><td>{{ position.nouvelle_station || '—' }}</td></tr><tr v-if="!store.data.positions?.length"><td colspan="4">Aucun déplacement enregistré.</td></tr></table></article><article class="card table-card"><header><p class="eyebrow">État</p><h2>Changements d’état</h2></header><table><tr><th>Date</th><th>Ancien état</th><th>Nouvel état</th><th>Motif</th></tr><tr v-for="state in store.data.states" :key="`${state.date_heure}-${state.nouvel_etat}`"><td>{{ $formatDateTime(state.date_heure) }}</td><td>{{ state.ancien_etat || '—' }}</td><td>{{ state.nouvel_etat }}</td><td>{{ state.motif_changement }}</td></tr><tr v-if="!store.data.states?.length"><td colspan="4">Aucun changement d’état enregistré.</td></tr></table></article></div>
    </section>
  </AppLayout>
</template>
