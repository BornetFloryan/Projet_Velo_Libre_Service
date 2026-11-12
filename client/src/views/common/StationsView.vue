<script setup>
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAppStore } from '../../stores/app.store';
const store = useAppStore(); const search = ref(''); const reporting = ref(null); const description = ref(''); onMounted(() => store.load('stations'));
const stations = computed(() => { const q = search.value.toLowerCase(); return !q ? store.data.stations || [] : (store.data.stations || []).filter(s => `${s.nom} ${s.adresse}`.toLowerCase().includes(q)); });
const bikesFor = s => (store.data.bikes || []).filter(b => Number(b.id_station) === Number(s.id_station));
const report = (bike, station) => { store.action('report_issue', { numero_serie: bike.numero_serie, id_station: station.id_station, description: description.value }, 'stations'); reporting.value = null; description.value = ''; };
</script>
<template>
    <AppLayout>
        <section class="stack">
            <div class="intro">
                <p class="eyebrow">Réseau en direct</p>
                <h2>Stations et vélos disponibles</h2>
                <p>Réservez un vélo pendant 15 minutes ou démarrez un trajet.</p>
            </div>
            <div class="card"><input v-model="search" class="search"
                    placeholder="Rechercher une station ou une adresse…"></div>
            <div class="station-grid">
                <article v-for="s in stations" :key="s.id_station" class="card station-card">
                    <header>
                        <div>
                            <p class="eyebrow">Station #{{ s.id_station }}</p>
                            <h2>{{ s.nom }}</h2>
                            <p class="muted">{{ s.adresse }} · {{ s.etat }}</p>
                        </div><span class="badge">{{ s.statut_alerte }}</span>
                    </header>
                    <div class="station-numbers">
                        <div><b>{{ s.velos_disponibles }}</b><span>vélos</span></div>
                        <div><b>{{ s.velos_mecaniques_disponibles }}</b><span>mécaniques</span></div>
                        <div><b>{{ s.velos_electriques_disponibles }}</b><span>électriques</span></div>
                    </div>
                    <p class="muted">{{ s.bornes_libres }} borne(s) libre(s) sur {{ s.nombre_bornes }}.</p>
                    <div class="bike-list">
                        <article v-for="bike in bikesFor(s)" :key="bike.numero_serie" class="bike-row">
                            <div><b>{{ bike.numero_serie }}</b><small> · {{ bike.categorie }} · borne {{
                                    bike.id_borne_actuelle }}</small></div><span class="badge">{{ bike.etat }}</span>
                            <div v-if="!store.isOperator" class="actions"><button v-if="bike.etat === 'disponible'"
                                    class="primary small"
                                    @click="store.action('start_trip', { numero_serie: bike.numero_serie, id_station_depart: s.id_station, id_borne_depart: bike.id_borne_actuelle }, 'trips')">Emprunter</button><button
                                    v-if="bike.etat === 'disponible'" class="secondary small"
                                    @click="store.action('reserve_bike', { numero_serie: bike.numero_serie }, 'stations')">Réserver
                                    15 min</button><button class="link small"
                                    @click="reporting = bike.numero_serie">Signaler</button></div>
                            <form v-if="reporting === bike.numero_serie" class="report-form"
                                @submit.prevent="report(bike, s)"><label>Décrivez le problème<input v-model="description"
                                        required></label><button class="danger-button small">Envoyer</button></form>
                        </article>
                    </div>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
