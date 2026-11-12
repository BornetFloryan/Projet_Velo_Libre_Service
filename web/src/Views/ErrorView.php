<?php

declare(strict_types=1);

function renderDatabaseError(): void
{
    renderHeader('Base indisponible', 'login', false);
    ?>
    <div class="row justify-content-center py-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-lg-5">
                    <p class="eyebrow">Connexion MySQL</p>
                    <h1 class="h3 fw-bold">La base de données est indisponible</h1>
                    <p class="text-secondary">Démarrez MySQL dans XAMPP puis importez d’abord <code>velo_libre_service_mysql.sql</code> et <code>donnees_test.sql</code>.</p>
                    <p class="text-secondary mb-0">Si vos identifiants diffèrent, copiez <code>web/config.example.php</code> vers <code>web/config.local.php</code> et adaptez-les.</p>
                </div>
            </div>
        </div>
    </div>
<?php
    renderFooter();
}
