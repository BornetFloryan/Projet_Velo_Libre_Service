<script setup>
import { onMounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAppStore } from '../../stores/app.store';
const store = useAppStore();
onMounted(() => store.load('statistics'));
</script>
<template><AppLayout><section class="stack"><div class="intro"><p class="eyebrow">Pilotage</p><h2>Statistiques du service</h2></div><div class="grid metrics"><article class="metric"><span>Recettes totales</span><strong>{{ store.data.totalRevenue }} €</strong></article><article class="metric"><span>Trajets terminés</span><strong>{{ store.data.completedTrips }}</strong></article><article class="metric"><span>Durée moyenne</span><strong>{{ Number(store.data.averageDuration || 0).toFixed(0) }} min</strong></article></div><div class="grid two"><article class="card table-card"><header><p class="eyebrow">Recettes</p><h2>Par formule</h2></header><table><tr><th>Formule</th><th>Trajets</th><th>Recettes</th></tr><tr v-for="f in store.data.formulaStats" :key="f.formule"><td>{{ f.formule }}</td><td>{{ f.nombre_trajets }}</td><td>{{ f.recettes }} €</td></tr></table></article><article class="card table-card"><header><p class="eyebrow">Activité</p><h2>Trajets par jour</h2></header><table><tr><th>Jour</th><th>Trajets</th><th>Recettes</th></tr><tr v-for="d in store.data.dailyStats" :key="d.jour"><td>{{ $formatDate(d.jour) }}</td><td>{{ d.nombre_trajets }}</td><td>{{ d.recettes }} €</td></tr></table></article></div></section></AppLayout></template>
