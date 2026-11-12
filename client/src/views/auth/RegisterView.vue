<script setup>
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.store';

const store = useAppStore();
const router = useRouter();

async function submit() {
  await store.registerUser();
  if (!store.error) router.push('/connexion');
}
</script>

<template>
  <main class="auth-page">
    <section class="auth-showcase">
      <div>
        <div class="brand auth-brand"><span>V</span> VéloLibre</div>
        <p class="eyebrow light">Mobilité partagée</p>
        <h1>Roulez<br>en liberté.</h1>
        <p>Créez votre compte pour emprunter, réserver et gérer vos trajets.</p>
      </div>
    </section>
    <section class="auth-form-panel">
      <div v-if="store.error" class="alert danger">{{ store.error }}</div>
      <div v-if="store.success" class="alert success">{{ store.success }}</div>
      <p class="eyebrow">Nouveau compte</p>
      <h2>Créer un compte usager</h2>
      <form @submit.prevent="submit">
        <div class="grid two">
          <label>Prénom<input v-model="store.register.prenom" required></label>
          <label>Nom<input v-model="store.register.nom" required></label>
        </div>
        <label>Adresse e-mail<input v-model="store.register.email" type="email" required></label>
        <label>Mot de passe <small>8 caractères minimum</small><input v-model="store.register.password" type="password" minlength="8" required></label>
        <button class="primary wide">Créer mon compte</button>
      </form>
      <RouterLink class="link centered" to="/connexion">Déjà inscrit ? Se connecter</RouterLink>
    </section>
  </main>
</template>
