<script setup>
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.store';

const store = useAppStore();
const router = useRouter();

async function submit() {
  await store.loginUser();
  if (store.actor) router.push(store.isOperator ? '/operateur' : '/usager');
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-showcase">
      <div>
        <div class="brand auth-brand"><span>V</span> VéloLibre</div>
        <p class="eyebrow light">Mobilité partagée</p>
        <h1>Le vélo partagé,<br>piloté en temps réel.</h1>
        <p>Stations, emprunts, maintenance et régulation réunis dans une seule interface.</p>
      </div>
      <div class="auth-stats">
        <div><b>4</b><span>stations</span></div>
        <div><b>9</b><span>vélos démo</span></div>
        <div><b>15 min</b><span>réservation</span></div>
      </div>
    </section>
    <section class="auth-form-panel">
      <div v-if="store.error" class="alert danger">{{ store.error }}</div>
      <p class="eyebrow">Espace sécurisé</p>
      <h2>Connexion</h2>
      <form @submit.prevent="submit">
        <label>Adresse e-mail<input v-model="store.login.email" type="email" required autofocus></label>
        <label>Mot de passe<input v-model="store.login.password" type="password" required></label>
        <button class="primary wide">Se connecter</button>
      </form>
      <RouterLink class="link centered" to="/inscription">Pas encore de compte ? Créer un compte usager</RouterLink>
      <aside class="demo-box">
        <b>Comptes de démonstration</b>
        <span><b>Usager</b> — alice.martin@example.test · <code>password</code><small>Emprunter, réserver, restituer et gérer son compte.</small></span>
        <span><b>Maintenance</b> — camille.leroy@example.test · <code>password</code><small>Traiter les signalements, créer et clôturer des interventions, gérer l’état des vélos.</small></span>
        <span><b>Régulation</b> — nolan.robert@example.test · <code>password</code><small>Planifier des tournées et enregistrer les déplacements de vélos entre stations.</small></span>
        <span><b>Administration</b> — sarah.thomas@example.test · <code>password</code><small>Accéder à l’ensemble des fonctionnalités, statistiques et réinitialisation de la base.</small></span>
      </aside>
    </section>
  </main>
</template>
