<script setup>
import { onMounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAppStore } from '../../stores/app.store';

const store = useAppStore();
onMounted(() => store.load('dashboard'));
</script>

<template>
  <AppLayout>
    <section class="stack">
      <div class="intro"><p class="eyebrow">Centre de supervision</p><h2>Tableau de bord opérateur</h2></div>
      <article v-if="store.data.overdueTrips?.length" class="alert warning"><b>Vélo(x) non restitué(s) depuis plus de 24 heures</b><p>Ces vélos restent indisponibles. Une vérification ou une intervention est nécessaire.</p><ul><li v-for="trip in store.data.overdueTrips" :key="trip.id_trajet"><b>{{ trip.numero_serie }}</b> · {{ trip.usager_nom }} · départ le {{ $formatDateTime(trip.date_depart) }} ({{ trip.duree_heures }} h)</li></ul></article>
      <div class="grid metrics"><article class="metric"><span>Vélos disponibles</span><strong>{{ store.data.availableBikes }}</strong></article><article class="metric"><span>Signalements ouverts</span><strong>{{ store.data.openReports }}</strong></article><article class="metric"><span>Interventions ouvertes</span><strong>{{ store.data.openInterventions }}</strong></article></div>
      <article class="card table-card"><header><p class="eyebrow">Maintenance</p><h2>Derniers signalements</h2></header><table><tr><th>Date</th><th>Station</th><th>Vélo</th><th>Description</th></tr><tr v-for="r in store.data.recentReports" :key="r.id_signalement"><td>{{ $formatDateTime(r.date_detection) }}</td><td>{{ r.station_nom }}</td><td>{{ r.numero_serie }}</td><td>{{ r.description }}</td></tr></table></article>
    </section>
  </AppLayout>
</template>
