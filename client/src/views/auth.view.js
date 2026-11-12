export default {
  props: ['mode', 'login', 'register', 'loading', 'error', 'success'],
  emits: ['login', 'register', 'toggle'],
  template: `
    <main class="auth-page">
      <section class="auth-showcase">
        <div><div class="brand auth-brand"><span>V</span> VéloLibre</div><p class="eyebrow light">Projet BD70 · UTBM</p><h1>Le vélo partagé,<br>piloté en temps réel.</h1><p>Stations, emprunts, maintenance et régulation réunis dans une seule interface.</p></div>
        <div class="auth-stats"><div><b>4</b><span>stations</span></div><div><b>9</b><span>vélos démo</span></div><div><b>15 min</b><span>réservation</span></div></div>
      </section>
      <section class="auth-form-panel">
        <div v-if="error" class="alert danger">{{ error }}</div><div v-if="success" class="alert success">{{ success }}</div>
        <template v-if="mode === 'login'"><p class="eyebrow">Espace sécurisé</p><h2>Connexion</h2><form @submit.prevent="$emit('login')"><label>Adresse e-mail<input v-model="login.email" type="email" autocomplete="email" required autofocus></label><label>Mot de passe<input v-model="login.password" type="password" autocomplete="current-password" required></label><button class="primary wide" :disabled="loading">Se connecter</button></form><button class="link centered" @click="$emit('toggle')">Pas encore de compte ? Créer un compte usager</button><aside class="demo-box"><b>Comptes de démonstration</b><span>alice.martin@example.test · <code>password</code></span><span>sarah.thomas@example.test · administration · <code>password</code></span></aside></template>
        <template v-else><p class="eyebrow">Nouveau compte</p><h2>Créer un compte usager</h2><form @submit.prevent="$emit('register')"><div class="grid two"><label>Prénom<input v-model="register.prenom" required></label><label>Nom<input v-model="register.nom" required></label></div><label>Adresse e-mail<input v-model="register.email" type="email" required></label><label>Mot de passe <small>8 caractères minimum</small><input v-model="register.password" type="password" minlength="8" required></label><button class="primary wide" :disabled="loading">Créer mon compte</button></form><button class="link centered" @click="$emit('toggle')">Déjà inscrit ? Se connecter</button></template>
      </section>
    </main>`
};
