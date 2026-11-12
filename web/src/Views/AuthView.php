<?php

declare(strict_types=1);

function renderLoginPage(bool $register = false): void
{
    renderHeader($register ? 'Créer un compte' : 'Connexion', 'login', false);
    ?>
    <div class="auth-shell row align-items-stretch g-0 shadow-lg overflow-hidden rounded-4">
        <section class="col-lg-6 auth-visual p-4 p-md-5 d-flex flex-column justify-content-between">
            <div>
                <div class="brand-mark brand-mark-large mb-4">V</div>
                <p class="eyebrow text-white-50">Projet BD70 · UTBM</p>
                <h1 class="display-5 fw-bold text-white">Le vélo partagé, piloté en temps réel.</h1>
                <p class="lead text-white-50 mt-3 mb-0">Stations, emprunts, maintenance et régulation réunis dans une seule interface.</p>
            </div>
            <div class="auth-stats row g-3 mt-4">
                <div class="col-4"><strong>4</strong><span>stations</span></div>
                <div class="col-4"><strong>9</strong><span>vélos démo</span></div>
                <div class="col-4"><strong>15 min</strong><span>réservation</span></div>
            </div>
        </section>
        <section class="col-lg-6 bg-white p-4 p-md-5">
            <?php if ($register): ?>
                <p class="eyebrow">Nouveau compte</p>
                <h2 class="h3 fw-bold mb-4">Créer un compte usager</h2>
                <form method="post" action="index.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="register">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="prenom">Prénom</label>
                            <input class="form-control" id="prenom" name="prenom" maxlength="80" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="nom">Nom</label>
                            <input class="form-control" id="nom" name="nom" maxlength="80" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="email">Adresse e-mail</label>
                            <input class="form-control" id="email" name="email" type="email" maxlength="255" required autocomplete="email">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="password">Mot de passe</label>
                            <input class="form-control" id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
                            <div class="form-text">8 caractères minimum.</div>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100 mt-4" type="submit">Créer mon compte</button>
                </form>
                <p class="text-center small text-secondary mt-4 mb-0">Déjà inscrit ? <a href="<?= e(appUrl('login')) ?>">Se connecter</a></p>
            <?php else: ?>
                <p class="eyebrow">Espace sécurisé</p>
                <h2 class="h3 fw-bold mb-4">Connexion</h2>
                <form method="post" action="index.php" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label class="form-label" for="email">Adresse e-mail</label>
                        <input class="form-control" id="email" name="email" type="email" required autocomplete="email" autofocus>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Mot de passe</label>
                        <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                </form>
                <p class="text-center small text-secondary mt-4 mb-3">Pas encore de compte ? <a href="<?= e(appUrl('register')) ?>">Créer un compte usager</a></p>
                <div class="demo-credentials rounded-3 p-3 small">
                    <strong class="d-block mb-1">Comptes de démonstration</strong>
                    <span>alice.martin@example.test · mot de passe : <code>password</code></span><br>
                    <span>sarah.thomas@example.test · administration · <code>password</code></span>
                </div>
            <?php endif; ?>
        </section>
    </div>
<?php
    renderFooter();
}
