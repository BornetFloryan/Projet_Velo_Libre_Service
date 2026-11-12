<script setup>
import { onMounted } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { useAppStore } from '../../stores/app.store';
const store = useAppStore(); onMounted(() => store.load('dashboard'));
</script>
<template>
    <AppLayout>
        <section class="stack">
            <div class="intro">
                <p class="eyebrow">Bonjour {{ store.data.user?.prenom || '' }}</p>
                <h2>Prêt à pédaler ?</h2>
                <p>Visualisez l’état du réseau et gérez votre trajet.</p>
            </div>
            <div class="grid metrics">
                <article class="metric"><span>Portefeuille</span><strong>{{ store.data.user?.solde_portefeuille ?? 0 }}
                        €</strong><small>Solde disponible</small></article>
                <article class="metric"><span>Abonnement</span><strong>{{ store.data.subscription?.formule_nom ||
                    'Aucun' }}</strong></article>
                <article class="metric"><span>Réservation</span><strong>{{ store.data.reservation?.numero_serie ||
                    'Aucune' }}</strong></article>
            </div>
            <div class="grid two">
                <article class="card">
                    <p class="eyebrow">Trajet actuel</p>
                    <h2>{{ store.data.openTrip?.numero_serie || 'Aucun trajet' }}</h2>
                    <p>{{ store.data.openTrip ? 'Depuis ' + store.data.openTrip.station_depart_nom : 'Choisissez un vélo disponible.' }}</p>
                    <RouterLink class="primary" :to="store.data.openTrip ? '/usager/trajets' : '/stations'">{{
                        store.data.openTrip ? 'Restituer le vélo' : 'Trouver un vélo' }}</RouterLink>
                </article>
                <article class="card">
                    <p class="eyebrow">Réservation</p>
                    <h2>{{ store.data.reservation?.numero_serie || 'Aucune réservation' }}</h2>
                    <p>{{ store.data.reservation?.station_nom || 'Réservez un vélo pendant 15 minutes.' }}</p><button
                        v-if="store.data.reservation" class="secondary"
                        @click="store.action('cancel_reservation', { id_reservation: store.data.reservation.id_reservation }, 'dashboard')">Annuler
                        la réservation</button>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
