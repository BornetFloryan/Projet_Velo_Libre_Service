<script setup>
import { onMounted, ref } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAppStore } from '../../stores/app.store';
const store = useAppStore();
const tab = ref('reports');
onMounted(() => store.load('operations'));
const act = (type, payload) => store.action(type, payload, 'operations');
</script>
<template>
    <AppLayout>
        <section class="stack">
            <div class="intro">
                <p class="eyebrow">Exploitation du réseau</p>
                <h2>Opérations</h2>
            </div>
            <div class="tabs"><button :class="{ active: tab === 'reports' }"
                    @click="tab = 'reports'">Signalements</button><button :class="{ active: tab === 'fleet' }"
                    @click="tab = 'fleet'">Flotte</button><button
                    v-if="['regulation', 'administration'].includes(store.actor.role)"
                    :class="{ active: tab === 'regulation' }" @click="tab = 'regulation'">Régulation</button><button
                    v-if="store.actor.role === 'administration'" :class="{ active: tab === 'admin' }"
                    @click="tab = 'admin'">Administration</button></div>
            <article v-if="tab === 'reports'" class="card table-card">
                <header>
                    <p class="eyebrow">Maintenance</p>
                    <h2>Signalements et interventions</h2>
                </header>
                <table>
                    <tr>
                        <th>Vélo / station</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                    <tr v-for="r in store.data.reports" :key="r.id_signalement">
                        <td><b>{{ r.numero_serie }}</b><br>{{ r.station_nom }}</td>
                        <td>{{ r.description }}</td>
                        <td><button v-if="!r.id_intervention" class="secondary small"
                                @click="act('create_intervention', { id_signalement: r.id_signalement, type_action: 'Diagnostic' })">Intervenir</button><button
                                v-else class="primary small"
                                @click="act('finish_intervention', { id_intervention: r.id_intervention })">Clôturer</button>
                        </td>
                    </tr>
                </table>
            </article>
            <article v-else-if="tab === 'fleet'" class="card table-card">
                <header>
                    <p class="eyebrow">Parc de vélos</p>
                    <h2>Flotte</h2>
                </header>
                <table>
                    <tr>
                        <th>Vélo</th>
                        <th>Catégorie</th>
                        <th>Station</th>
                        <th>État</th>
                        <th></th>
                    </tr>
                    <tr v-for="b in store.data.bikes" :key="b.numero_serie">
                        <td>{{ b.numero_serie }}</td>
                        <td>{{ b.categorie }}</td>
                        <td>{{ b.station_nom || 'Atelier' }}</td>
                        <td><select v-model="b.etat">
                                <option v-for="s in ['disponible', 'maintenance', 'atelier', 'hors_service']" :key="s">{{ s
                                    }}</option>
                            </select></td>
                        <td><button class="secondary small"
                                @click="act('update_bike', { numero_serie: b.numero_serie, etat: b.etat, niveau_batterie: b.niveau_batterie })">Enregistrer</button>
                        </td>
                    </tr>
                </table>
            </article>
            <div v-else-if="tab === 'regulation'" class="grid two">
                <article class="card">
                    <p class="eyebrow">Nouvelle tournée</p>
                    <h2>Planifier</h2><label>Date<input v-model="store.form.date_tournee"
                            type="date"></label><label>Véhicule<input
                            v-model="store.form.immatriculation_vehicule"></label><button class="primary"
                        @click="act('create_tournee', store.form)">Créer</button>
                </article>
                <article class="card">
                    <p class="eyebrow">Mouvement</p>
                    <h2>Retrait ou dépôt</h2><label>Tournée<select v-model="store.form.id_tournee">
                            <option v-for="t in store.data.tours" :key="t.id_tournee" :value="t.id_tournee">#{{
                                t.id_tournee }} · {{ $formatDate(t.date_tournee) }}</option>
                        </select></label><label>Vélo<select v-model="store.form.numero_serie">
                            <option v-for="b in store.data.bikes" :key="b.numero_serie" :value="b.numero_serie">{{
                                b.numero_serie }}</option>
                        </select></label><label>Station<select v-model="store.form.id_station">
                            <option v-for="s in store.data.stations" :key="s.id_station" :value="s.id_station">{{ s.nom
                                }}</option>
                        </select></label><label>Nature<select v-model="store.form.nature">
                            <option value="retrait">Retrait</option>
                            <option value="depot">Dépôt</option>
                        </select></label><button class="secondary"
                        @click="act('add_movement', store.form)">Enregistrer</button>
                </article>
            </div>
            <div v-else class="grid two">
                <article class="card">
                    <p class="eyebrow">Stations</p>
                    <h2>Administration</h2>
                    <div v-for="s in store.data.stations" :key="s.id_station" class="row"><b>{{ s.nom }}</b><button
                            class="secondary small"
                            @click="act('update_station', { id_station: s.id_station, etat: s.etat, seuil_velos_min: s.seuil_velos_min, seuil_bornes_min: s.seuil_bornes_min })">Enregistrer</button>
                    </div>
                </article>
                <article class="card">
                    <p class="eyebrow">Tarification</p>
                    <h2>Créer une formule</h2><label>Nom<input v-model="store.form.nom"></label><label>Prix de
                        base<input v-model="store.form.prix_base" type="number"></label><label>Durée incluse<input
                            v-model="store.form.duree_gratuite_minutes" type="number"></label><label>Tarif /
                        tranche<input v-model="store.form.tarif_par_tranche" type="number"></label><label>Supplément
                        électrique<input v-model="store.form.supplement_electrique" type="number"></label><button
                        class="primary" @click="act('create_formula', store.form)">Créer</button>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
