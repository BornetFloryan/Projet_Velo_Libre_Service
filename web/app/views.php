<?php

declare(strict_types=1);

function stationData(PDO $pdo): array
{
    return fetchAllRows(
        $pdo,
        'SELECT ves.*, s.adresse, s.latitude, s.longitude, s.seuil_velos_min, s.seuil_bornes_min
         FROM v_etat_station ves
         JOIN station s ON s.id_station = ves.id_station
         ORDER BY ves.nom'
    );
}

function bikesByStation(PDO $pdo): array
{
    $bikes = fetchAllRows(
        $pdo,
        'SELECT v.numero_serie, v.etat, v.niveau_batterie, v.id_borne_actuelle, b.id_station,
                c.libelle AS categorie, s.nom AS station_nom
         FROM velo v
         LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle
         JOIN categorie_velo c ON c.id_categorie = v.id_categorie
         LEFT JOIN station s ON s.id_station = b.id_station
         ORDER BY s.nom, b.id_borne, v.numero_serie'
    );

    $byStation = [];
    foreach ($bikes as $bike) {
        if ($bike['id_station'] !== null) {
            $byStation[(int) $bike['id_station']][] = $bike;
        }
    }

    return $byStation;
}

function freeBores(PDO $pdo): array
{
    return fetchAllRows(
        $pdo,
        'SELECT b.id_borne, b.id_station, s.nom AS station_nom
         FROM borne b
         JOIN station s ON s.id_station = b.id_station
         LEFT JOIN velo v ON v.id_borne_actuelle = b.id_borne
         WHERE b.etat = "disponible" AND v.numero_serie IS NULL
         ORDER BY s.nom, b.id_borne'
    );
}

function renderMetric(string $label, string|int|float $value, string $hint, string $accent = 'primary'): void
{
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card h-100 border-0 shadow-sm metric-<?= e($accent) ?>">
            <div class="card-body">
                <p class="text-uppercase small fw-semibold text-secondary mb-2"><?= e($label) ?></p>
                <p class="display-6 fw-bold mb-1"><?= e((string) $value) ?></p>
                <p class="mb-0 small text-secondary"><?= e($hint) ?></p>
            </div>
        </div>
    </div>
<?php
}

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

function renderDashboard(PDO $pdo): void
{
    if (isUsager()) {
        renderUserDashboard($pdo);
        return;
    }

    renderOperatorDashboard($pdo);
}

function renderUserDashboard(PDO $pdo): void
{
    $actor = requireUsager();
    $user = fetchOne($pdo, 'SELECT * FROM usager WHERE id_usager = :id', ['id' => $actor['id']]);
    $openTrip = fetchOne(
        $pdo,
        'SELECT t.*, s.nom AS station_depart_nom, b.id_borne AS borne_depart
         FROM trajet t
         JOIN station s ON s.id_station = t.id_station_depart
         JOIN borne b ON b.id_borne = t.id_borne_depart
         WHERE t.id_usager = :id AND t.date_arrivee IS NULL
         ORDER BY t.date_depart DESC LIMIT 1',
        ['id' => $actor['id']]
    );
    $reservation = fetchOne(
        $pdo,
        'SELECT r.*, b.id_borne, s.nom AS station_nom
         FROM reservation r
         LEFT JOIN velo v ON v.numero_serie = r.numero_serie
         LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle
         LEFT JOIN station s ON s.id_station = b.id_station
         WHERE r.id_usager = :id AND r.statut = "active"
         ORDER BY r.date_reservation DESC LIMIT 1',
        ['id' => $actor['id']]
    );
    $subscription = fetchOne(
        $pdo,
        'SELECT a.*, f.nom AS formule_nom, f.duree_gratuite_minutes
         FROM abonnement a JOIN formule f ON f.id_formule = a.id_formule
         WHERE a.id_usager = :id AND CURDATE() BETWEEN a.date_debut AND a.date_fin
         ORDER BY a.date_fin DESC LIMIT 1',
        ['id' => $actor['id']]
    );
    $stations = stationData($pdo);

    renderHeader('Tableau de bord', 'dashboard');
    renderPageTitle('Bonjour ' . ($user['prenom'] ?? ''), 'Prêt à pédaler ?', 'Visualisez l’état du réseau et gérez votre trajet en quelques secondes.');
    ?>
    <div class="row g-3 g-lg-4 mb-4">
        <?php renderMetric('Portefeuille', money($user['solde_portefeuille'] ?? 0), 'Solde disponible', 'primary'); ?>
        <?php renderMetric('Abonnement', $subscription['formule_nom'] ?? 'Aucun', $subscription ? 'Valide jusqu’au ' . frenchDate($subscription['date_fin'], false) : 'Choisissez une formule', 'success'); ?>
        <?php renderMetric('Trajet en cours', $openTrip ? 'Oui' : 'Non', $openTrip ? 'Depuis ' . frenchDate($openTrip['date_depart']) : 'Aucun vélo emprunté', $openTrip ? 'warning' : 'secondary'); ?>
        <?php renderMetric('Stations actives', count(array_filter($stations, static fn (array $station): bool => $station['etat'] === 'en_service')), 'Dans le réseau', 'info'); ?>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <?php if ($openTrip !== null): ?>
                <section class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <p class="eyebrow mb-1">Trajet en cours</p>
                                <h2 class="h4 fw-bold mb-1"><?= e($openTrip['numero_serie']) ?></h2>
                                <p class="text-secondary mb-0">Départ : <?= e($openTrip['station_depart_nom']) ?> · borne <?= e((string) $openTrip['borne_depart']) ?></p>
                            </div>
                            <?= statusBadge('en_cours') ?>
                        </div>
                        <?php renderReturnForm($pdo, $openTrip); ?>
                    </div>
                </section>
            <?php elseif ($reservation !== null): ?>
                <section class="card border-0 shadow-sm h-100 reservation-card">
                    <div class="card-body p-4">
                        <p class="eyebrow mb-1">Réservation active</p>
                        <h2 class="h4 fw-bold"><?= e($reservation['numero_serie']) ?></h2>
                        <p class="text-secondary"><?= e($reservation['station_nom'] ?? 'Station en cours de mise à jour') ?> · borne <?= e((string) ($reservation['id_borne'] ?? '—')) ?></p>
                        <p class="small text-secondary">Réservé le <?= e(frenchDate($reservation['date_reservation'])) ?>. La réservation expire automatiquement après 15 minutes.</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-primary" href="<?= e(appUrl('stations')) ?>">Emprunter le vélo</a>
                            <form method="post" action="index.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="cancel_reservation">
                                <input type="hidden" name="id_reservation" value="<?= e((string) $reservation['id_reservation']) ?>">
                                <input type="hidden" name="return_page" value="dashboard">
                                <button class="btn btn-outline-secondary" type="submit">Annuler</button>
                            </form>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <section class="card border-0 shadow-sm h-100 quick-start-card">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <p class="eyebrow mb-1">Prochain trajet</p>
                            <h2 class="h4 fw-bold">Trouvez un vélo disponible</h2>
                            <p class="text-secondary mb-0">Les disponibilités viennent directement de la vue MySQL <code>v_etat_station</code>.</p>
                        </div>
                        <a class="btn btn-primary align-self-start mt-4" href="<?= e(appUrl('stations')) ?>">Voir les stations</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        <div class="col-xl-5">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="eyebrow mb-1">État du réseau</p>
                            <h2 class="h5 fw-bold mb-0">Stations en direct</h2>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(appUrl('stations')) ?>">Tout voir</a>
                    </div>
                    <div class="vstack gap-3">
                        <?php foreach (array_slice($stations, 0, 4) as $station): ?>
                            <div class="station-mini">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong><?= e($station['nom']) ?></strong>
                                    <?= statusBadge($station['statut_alerte']) ?>
                                </div>
                                <div class="small text-secondary mt-1"><?= e((string) $station['velos_disponibles']) ?> vélo(x) · <?= e((string) $station['bornes_libres']) ?> place(s) libre(s)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php
    renderFooter();
}

function renderReturnForm(PDO $pdo, array $openTrip): void
{
    $bores = freeBores($pdo);
    if ($bores === []) {
        echo '<div class="alert alert-warning mb-0">Aucune borne libre n’est disponible pour le moment.</div>';
        return;
    }
    ?>
    <form method="post" action="index.php" class="row g-3 mt-1" data-return-form>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="return_trip">
        <input type="hidden" name="id_trajet" value="<?= e((string) $openTrip['id_trajet']) ?>">
        <div class="col-md-5">
            <label class="form-label" for="station-arrivee">Station de restitution</label>
            <select class="form-select" id="station-arrivee" name="id_station_arrivee" required data-station-select>
                <option value="">Choisir…</option>
                <?php foreach (stationData($pdo) as $station): ?>
                    <option value="<?= e((string) $station['id_station']) ?>"><?= e($station['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="borne-arrivee">Borne libre</label>
            <select class="form-select" id="borne-arrivee" name="id_borne_arrivee" required data-borne-select>
                <option value="">Choisir…</option>
                <?php foreach ($bores as $borne): ?>
                    <option value="<?= e((string) $borne['id_borne']) ?>" data-station="<?= e((string) $borne['id_station']) ?>">
                        <?= e($borne['station_nom']) ?> · borne <?= e((string) $borne['id_borne']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-success w-100" type="submit">Restituer</button>
        </div>
    </form>
<?php
}

function renderStationsPage(PDO $pdo): void
{
    $stations = stationData($pdo);
    $bikes = bikesByStation($pdo);
    $openTrip = null;
    if (isUsager()) {
        $actor = requireUsager();
        $openTrip = fetchOne($pdo, 'SELECT id_trajet FROM trajet WHERE id_usager = :id AND date_arrivee IS NULL', ['id' => $actor['id']]);
    }

    renderHeader('Stations', 'stations');
    renderPageTitle('Réseau en direct', 'Stations et vélos disponibles', 'Recherchez une station, réservez un vélo pendant 15 minutes ou démarrez un trajet.');
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <label class="visually-hidden" for="station-search">Rechercher une station</label>
            <input class="form-control form-control-lg" id="station-search" type="search" placeholder="Rechercher une station ou une adresse…" data-station-search>
        </div>
    </div>
    <div class="row g-4" data-station-list>
        <?php foreach ($stations as $station): ?>
            <div class="col-lg-6 station-item" data-search="<?= e(mb_strtolower($station['nom'] . ' ' . $station['etat'])) ?>">
                <article class="card border-0 shadow-sm h-100 station-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <p class="eyebrow mb-1">Station #<?= e((string) $station['id_station']) ?></p>
                                <h2 class="h4 fw-bold mb-1"><?= e($station['nom']) ?></h2>
                                <p class="text-secondary small mb-0">État : <?= e(statusLabel($station['etat'])) ?></p>
                            </div>
                            <div class="text-end">
                                <?= statusBadge($station['statut_alerte']) ?>
                                <a class="d-block small mt-2" target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat=<?= e((string) $station['latitude'] ?? '') ?>&amp;mlon=<?= e((string) $station['longitude'] ?? '') ?>#map=17/<?= e((string) $station['latitude'] ?? '') ?>/<?= e((string) $station['longitude'] ?? '') ?>">Localiser</a>
                            </div>
                        </div>
                        <div class="row g-2 my-3 text-center">
                            <div class="col-4"><div class="station-count"><strong><?= e((string) $station['velos_disponibles']) ?></strong><span>vélos</span></div></div>
                            <div class="col-4"><div class="station-count"><strong><?= e((string) $station['velos_mecaniques_disponibles']) ?></strong><span>mécaniques</span></div></div>
                            <div class="col-4"><div class="station-count"><strong><?= e((string) $station['velos_electriques_disponibles']) ?></strong><span>électriques</span></div></div>
                        </div>
                        <p class="small text-secondary mb-3"><?= e((string) $station['bornes_libres']) ?> borne(s) libre(s) sur <?= e((string) $station['nombre_bornes']) ?>.</p>
                        <div class="vstack gap-2">
                            <?php $stationBikes = $bikes[(int) $station['id_station']] ?? []; ?>
                            <?php if ($stationBikes === []): ?>
                                <p class="small text-secondary mb-0">Aucun vélo actuellement rattaché à cette station.</p>
                            <?php endif; ?>
                            <?php foreach ($stationBikes as $bike): ?>
                                <div class="bike-row rounded-3 p-3 <?= $bike['etat'] === 'disponible' ? 'bike-row-ready' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong><?= e($bike['numero_serie']) ?></strong>
                                            <span class="text-secondary small">· <?= e($bike['categorie']) ?> · borne <?= e((string) $bike['id_borne_actuelle']) ?></span>
                                            <?php if ($bike['niveau_batterie'] !== null): ?>
                                                <span class="text-secondary small">· batterie <?= e((string) $bike['niveau_batterie']) ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                        <?= statusBadge($bike['etat']) ?>
                                    </div>
                                    <?php if (isUsager()): ?>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php if ($bike['etat'] === 'disponible' && $openTrip === null): ?>
                                                <form method="post" action="index.php">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="start_trip">
                                                    <input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                                                    <input type="hidden" name="id_station_depart" value="<?= e((string) $station['id_station']) ?>">
                                                    <input type="hidden" name="id_borne_depart" value="<?= e((string) $bike['id_borne_actuelle']) ?>">
                                                    <button class="btn btn-sm btn-primary" type="submit">Emprunter</button>
                                                </form>
                                                <form method="post" action="index.php">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="reserve_bike">
                                                    <input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                                                    <input type="hidden" name="return_page" value="stations">
                                                    <button class="btn btn-sm btn-outline-primary" type="submit">Réserver 15 min</button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#report-<?= e($bike['numero_serie']) ?>">Signaler</button>
                                        </div>
                                        <div class="collapse mt-2" id="report-<?= e($bike['numero_serie']) ?>">
                                            <form method="post" action="index.php" class="border-top pt-2">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="report_issue">
                                                <input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                                                <input type="hidden" name="id_station" value="<?= e((string) $station['id_station']) ?>">
                                                <input type="hidden" name="return_page" value="stations">
                                                <label class="form-label small" for="description-<?= e($bike['numero_serie']) ?>">Décrivez le problème</label>
                                                <div class="input-group">
                                                    <input class="form-control form-control-sm" id="description-<?= e($bike['numero_serie']) ?>" name="description" maxlength="2000" required>
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Envoyer</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php if ($bike['etat'] === 'disponible' && $openTrip !== null): ?>
                                        <p class="small text-secondary mt-2 mb-0">Restituez votre trajet en cours avant d’emprunter un autre vélo.</p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    renderFooter();
}

function renderTripsPage(PDO $pdo): void
{
    $actor = requireUsager();
    $openTrip = fetchOne(
        $pdo,
        'SELECT t.*, ds.nom AS station_depart_nom, db.id_borne AS borne_depart
         FROM trajet t
         JOIN station ds ON ds.id_station = t.id_station_depart
         JOIN borne db ON db.id_borne = t.id_borne_depart
         WHERE t.id_usager = :id AND t.date_arrivee IS NULL
         ORDER BY t.date_depart DESC LIMIT 1',
        ['id' => $actor['id']]
    );
    $reservation = fetchOne(
        $pdo,
        'SELECT r.*, s.nom AS station_nom, b.id_borne
         FROM reservation r LEFT JOIN velo v ON v.numero_serie = r.numero_serie
         LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle
         LEFT JOIN station s ON s.id_station = b.id_station
         WHERE r.id_usager = :id AND r.statut = "active" ORDER BY r.date_reservation DESC LIMIT 1',
        ['id' => $actor['id']]
    );
    $trips = fetchAllRows(
        $pdo,
        'SELECT t.*, ds.nom AS station_depart_nom, ar.nom AS station_arrivee_nom
         FROM trajet t
         JOIN station ds ON ds.id_station = t.id_station_depart
         LEFT JOIN station ar ON ar.id_station = t.id_station_arrivee
         WHERE t.id_usager = :id
         ORDER BY t.date_depart DESC LIMIT 20',
        ['id' => $actor['id']]
    );

    renderHeader('Mes trajets', 'trajets');
    renderPageTitle('Mobilité personnelle', 'Mes trajets', 'Empruntez, restituez et consultez l’historique de vos déplacements.');
    ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Trajet en cours</p>
                    <?php if ($openTrip !== null): ?>
                        <h2 class="h4 fw-bold"><?= e($openTrip['numero_serie']) ?> <span class="text-secondary fw-normal">depuis <?= e(frenchDate($openTrip['date_depart'])) ?></span></h2>
                        <p class="text-secondary">Départ : <?= e($openTrip['station_depart_nom']) ?> · borne <?= e((string) $openTrip['borne_depart']) ?></p>
                        <?php renderReturnForm($pdo, $openTrip); ?>
                    <?php else: ?>
                        <h2 class="h4 fw-bold">Aucun trajet en cours</h2>
                        <p class="text-secondary">Choisissez un vélo disponible dans une station.</p>
                        <a class="btn btn-primary" href="<?= e(appUrl('stations')) ?>">Trouver un vélo</a>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Réservation</p>
                    <?php if ($reservation !== null): ?>
                        <h2 class="h4 fw-bold"><?= e($reservation['numero_serie']) ?></h2>
                        <p class="text-secondary"><?= e($reservation['station_nom'] ?? 'Position inconnue') ?> · borne <?= e((string) ($reservation['id_borne'] ?? '—')) ?></p>
                        <form method="post" action="index.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="cancel_reservation">
                            <input type="hidden" name="id_reservation" value="<?= e((string) $reservation['id_reservation']) ?>">
                            <input type="hidden" name="return_page" value="trajets">
                            <button class="btn btn-outline-secondary" type="submit">Annuler la réservation</button>
                        </form>
                    <?php else: ?>
                        <h2 class="h4 fw-bold">Aucune réservation</h2>
                        <p class="text-secondary">Une réservation bloque un vélo durant 15 minutes.</p>
                        <a class="btn btn-outline-primary" href="<?= e(appUrl('stations')) ?>">Réserver un vélo</a>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <section class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h4 fw-bold mb-0">Derniers trajets</h2></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Départ</th><th>Vélo</th><th>Itinéraire</th><th>Durée</th><th>Coût</th><th>Évaluation</th></tr></thead>
                    <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?= e(frenchDate($trip['date_depart'])) ?></td>
                            <td><strong><?= e($trip['numero_serie']) ?></strong><br><?= statusBadge($trip['date_arrivee'] === null ? 'en_cours' : $trip['statut_paiement']) ?></td>
                            <td class="small"><?= e($trip['station_depart_nom']) ?> → <?= e($trip['station_arrivee_nom'] ?? 'En cours') ?></td>
                            <td><?= $trip['duree_minutes'] === null ? '—' : e((string) $trip['duree_minutes']) . ' min' ?></td>
                            <td><?= $trip['date_arrivee'] === null ? '—' : e(money($trip['cout_facture'])) ?></td>
                            <td>
                                <?php if ($trip['date_arrivee'] !== null): ?>
                                    <form method="post" action="index.php" class="d-flex gap-1 align-items-center">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="rate_trip">
                                        <input type="hidden" name="id_trajet" value="<?= e((string) $trip['id_trajet']) ?>">
                                        <input type="hidden" name="return_page" value="trajets">
                                        <select class="form-select form-select-sm" name="note_evaluation" aria-label="Note du trajet">
                                            <?php for ($note = 1; $note <= 5; $note++): ?>
                                                <option value="<?= e((string) $note) ?>" <?= (int) $trip['note_evaluation'] === $note ? 'selected' : '' ?>><?= e((string) $note) ?>/5</option>
                                            <?php endfor; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary" type="submit">OK</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($trips === []): ?><tr><td colspan="6" class="text-center text-secondary py-4">Aucun trajet enregistré.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php
    renderFooter();
}

function renderAccountPage(PDO $pdo): void
{
    $actor = requireUsager();
    $user = fetchOne($pdo, 'SELECT * FROM usager WHERE id_usager = :id', ['id' => $actor['id']]);
    $subscriptions = fetchAllRows(
        $pdo,
        'SELECT a.*, f.nom AS formule_nom, f.prix_base, f.duree_gratuite_minutes, f.tarif_par_tranche, f.supplement_electrique
         FROM abonnement a JOIN formule f ON f.id_formule = a.id_formule
         WHERE a.id_usager = :id ORDER BY a.date_fin DESC',
        ['id' => $actor['id']]
    );
    $operations = fetchAllRows(
        $pdo,
        'SELECT o.*, t.numero_serie FROM operation_portefeuille o
         LEFT JOIN trajet t ON t.id_trajet = o.id_trajet
         WHERE o.id_usager = :id ORDER BY o.date_heure DESC LIMIT 15',
        ['id' => $actor['id']]
    );
    $formulas = fetchAllRows($pdo, 'SELECT * FROM formule ORDER BY prix_base, nom');

    renderHeader('Mon compte', 'compte');
    renderPageTitle('Espace personnel', 'Mon compte', 'Gérez votre profil, vos abonnements et votre portefeuille.');
    ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm h-100 wallet-card">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Portefeuille</p>
                    <p class="display-5 fw-bold mb-1"><?= e(money($user['solde_portefeuille'])) ?></p>
                    <p class="text-secondary">Solde utilisable lors de la restitution.</p>
                    <form method="post" action="index.php" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="recharge_wallet">
                        <div class="input-group">
                            <input class="form-control" type="number" name="montant" min="1" max="200" step="0.01" value="10" required aria-label="Montant à recharger">
                            <button class="btn btn-primary" type="submit">Recharger</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Profil</p>
                    <form method="post" action="index.php" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="col-md-4"><label class="form-label">Prénom</label><input class="form-control" name="prenom" maxlength="80" value="<?= e($user['prenom']) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Nom</label><input class="form-control" name="nom" maxlength="80" value="<?= e($user['nom']) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">E-mail</label><input class="form-control" name="email" type="email" maxlength="255" value="<?= e($user['email']) ?>" required></div>
                        <div class="col-12"><button class="btn btn-outline-primary" type="submit">Enregistrer le profil</button></div>
                    </form>
                </div>
            </section>
        </div>
    </div>
    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-end mb-3"><div><p class="eyebrow mb-1">Abonnements</p><h2 class="h4 fw-bold mb-0">Mes formules</h2></div></div>
        <div class="row g-4">
            <?php foreach ($formulas as $formula): ?>
                <div class="col-md-4">
                    <article class="card border-0 shadow-sm h-100 formula-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h5 fw-bold"><?= e($formula['nom']) ?></h3>
                            <p class="display-6 fw-bold mb-0"><?= e(money($formula['prix_base'])) ?></p>
                            <p class="small text-secondary">+ <?= e(money($formula['tarif_par_tranche'])) ?> / tranche de 30 min après <?= e((string) $formula['duree_gratuite_minutes']) ?> min.</p>
                            <ul class="small text-secondary ps-3 mb-4">
                                <li><?= e((string) $formula['duree_gratuite_minutes']) ?> min incluses</li>
                                <li>Supplément électrique : <?= e(money($formula['supplement_electrique'])) ?></li>
                            </ul>
                            <form method="post" action="index.php" class="mt-auto">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="buy_subscription">
                                <input type="hidden" name="id_formule" value="<?= e((string) $formula['id_formule']) ?>">
                                <button class="btn btn-outline-primary w-100" type="submit">Choisir cette formule</button>
                            </form>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h5 fw-bold mb-0">Opérations portefeuille</h2></div>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Date</th><th>Nature</th><th class="text-end">Montant</th></tr></thead><tbody>
                    <?php foreach ($operations as $operation): ?>
                        <tr><td><?= e(frenchDate($operation['date_heure'])) ?></td><td><?= e(statusLabel($operation['nature'])) ?><?= $operation['numero_serie'] ? ' · ' . e($operation['numero_serie']) : '' ?></td><td class="text-end <?= (float) $operation['montant'] < 0 ? 'text-danger' : 'text-success' ?>"><?= e(money($operation['montant'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h5 fw-bold mb-0">Abonnements souscrits</h2></div>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Formule</th><th>Période</th><th>État</th></tr></thead><tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <?php $active = date('Y-m-d') >= $subscription['date_debut'] && date('Y-m-d') <= $subscription['date_fin']; ?>
                        <tr><td><?= e($subscription['formule_nom']) ?></td><td><?= e(frenchDate($subscription['date_debut'], false)) ?> → <?= e(frenchDate($subscription['date_fin'], false)) ?></td><td><?= statusBadge($active ? 'active' : 'expiree') ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
    </div>
<?php
    renderFooter();
}

function renderOperatorDashboard(PDO $pdo): void
{
    $stations = stationData($pdo);
    $availableBikes = (int) fetchValue($pdo, 'SELECT COUNT(*) FROM velo WHERE etat = "disponible"');
    $openReports = (int) fetchValue($pdo, 'SELECT COUNT(*) FROM signalement s LEFT JOIN intervention i ON i.id_signalement = s.id_signalement WHERE i.id_intervention IS NULL');
    $openInterventions = (int) fetchValue($pdo, 'SELECT COUNT(*) FROM intervention WHERE date_fin IS NULL');
    $alerts = array_values(array_filter($stations, static fn (array $station): bool => $station['statut_alerte'] !== 'normale' || $station['etat'] !== 'en_service'));
    $recentReports = fetchAllRows(
        $pdo,
        'SELECT s.*, st.nom AS station_nom, CONCAT(u.prenom, " ", u.nom) AS usager_nom
         FROM signalement s JOIN station st ON st.id_station = s.id_station
         JOIN usager u ON u.id_usager = s.id_usager
         ORDER BY s.date_detection DESC LIMIT 5'
    );

    renderHeader('Tableau de bord opérateur', 'dashboard');
    renderPageTitle('Centre de supervision', 'Bonjour ' . (currentActor()['name'] ?? ''), 'Suivez le réseau, les alertes et les opérations techniques.');
    ?>
    <div class="row g-3 g-lg-4 mb-4">
        <?php renderMetric('Vélos disponibles', $availableBikes, 'Prêts à être empruntés', 'success'); ?>
        <?php renderMetric('Alertes station', count($alerts), 'Stations à surveiller', count($alerts) > 0 ? 'warning' : 'success'); ?>
        <?php renderMetric('Signalements ouverts', $openReports, 'Sans intervention associée', $openReports > 0 ? 'danger' : 'success'); ?>
        <?php renderMetric('Interventions', $openInterventions, 'En cours', $openInterventions > 0 ? 'warning' : 'secondary'); ?>
    </div>
    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center"><div><p class="eyebrow mb-1">Priorités</p><h2 class="h4 fw-bold mb-0">Stations à surveiller</h2></div><a class="btn btn-sm btn-outline-primary" href="<?= e(appUrl('operations')) ?>">Gérer</a></div>
                    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Station</th><th>État</th><th>Vélos</th><th>Places</th><th>Alerte</th></tr></thead><tbody>
                    <?php foreach ($alerts as $station): ?>
                        <tr><td><strong><?= e($station['nom']) ?></strong></td><td><?= statusBadge($station['etat']) ?></td><td><?= e((string) $station['velos_disponibles']) ?></td><td><?= e((string) $station['bornes_libres']) ?></td><td><?= statusBadge($station['statut_alerte']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if ($alerts === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Aucune alerte active.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3"><div><p class="eyebrow mb-1">Maintenance</p><h2 class="h5 fw-bold mb-0">Derniers signalements</h2></div><a class="btn btn-sm btn-outline-primary" href="<?= e(appUrl('operations', ['tab' => 'signalements'])) ?>">Voir tout</a></div>
                    <div class="vstack gap-3">
                        <?php foreach ($recentReports as $report): ?>
                            <div class="report-mini"><strong><?= e($report['numero_serie']) ?></strong> · <?= e($report['station_nom']) ?><p class="small text-secondary mb-0 mt-1"><?= e(mb_strimwidth($report['description'], 0, 95, '…')) ?></p></div>
                        <?php endforeach; ?>
                        <?php if ($recentReports === []): ?><p class="text-secondary mb-0">Aucun signalement.</p><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php
    renderFooter();
}

function renderOperationsPage(PDO $pdo): void
{
    $actor = requireOperator();
    $allowedTabs = ['signalements', 'flotte', 'regulation'];
    if ($actor['role'] === 'administration') {
        $allowedTabs[] = 'administration';
    }
    $tab = (string) ($_GET['tab'] ?? 'signalements');
    if (!in_array($tab, $allowedTabs, true)) {
        $tab = 'signalements';
    }

    $stations = stationData($pdo);
    $allBikes = fetchAllRows(
        $pdo,
        'SELECT v.*, c.libelle AS categorie, b.id_station, s.nom AS station_nom
         FROM velo v
         JOIN categorie_velo c ON c.id_categorie = v.id_categorie
         LEFT JOIN borne b ON b.id_borne = v.id_borne_actuelle
         LEFT JOIN station s ON s.id_station = b.id_station
         ORDER BY v.etat, v.numero_serie'
    );
    $freeBoreRows = freeBores($pdo);

    renderHeader('Opérations', 'operations');
    renderPageTitle('Exploitation du réseau', 'Opérations', 'Maintenance, flotte, régulation et administration des données métier.');
    ?>
    <ul class="nav nav-pills flex-wrap gap-2 mb-4">
        <li class="nav-item"><a class="nav-link <?= $tab === 'signalements' ? 'active' : '' ?>" href="<?= e(appUrl('operations', ['tab' => 'signalements'])) ?>">Signalements</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'flotte' ? 'active' : '' ?>" href="<?= e(appUrl('operations', ['tab' => 'flotte'])) ?>">Flotte</a></li>
        <li class="nav-item"><a class="nav-link <?= $tab === 'regulation' ? 'active' : '' ?>" href="<?= e(appUrl('operations', ['tab' => 'regulation'])) ?>">Régulation</a></li>
        <?php if ($actor['role'] === 'administration'): ?><li class="nav-item"><a class="nav-link <?= $tab === 'administration' ? 'active' : '' ?>" href="<?= e(appUrl('operations', ['tab' => 'administration'])) ?>">Administration</a></li><?php endif; ?>
    </ul>
    <?php
    if ($tab === 'signalements') {
        renderReportsOperations($pdo, $actor);
    } elseif ($tab === 'flotte') {
        renderFleetOperations($pdo, $allBikes, $freeBoreRows);
    } elseif ($tab === 'regulation') {
        renderRegulationOperations($pdo, $actor, $stations, $allBikes);
    } else {
        renderAdministrationOperations($pdo, $stations);
    }
    renderFooter();
}

function renderReportsOperations(PDO $pdo, array $actor): void
{
    $reports = fetchAllRows(
        $pdo,
        'SELECT s.id_signalement, s.date_detection, s.description, s.numero_serie,
                st.nom AS station_nom, CONCAT(u.prenom, " ", u.nom) AS usager_nom,
                v.etat AS etat_velo,
                COUNT(i.id_intervention) AS nombre_interventions
         FROM signalement s
         JOIN station st ON st.id_station = s.id_station
         JOIN usager u ON u.id_usager = s.id_usager
         JOIN velo v ON v.numero_serie = s.numero_serie
         LEFT JOIN intervention i ON i.id_signalement = s.id_signalement
         GROUP BY s.id_signalement, s.date_detection, s.description, s.numero_serie, st.nom, u.prenom, u.nom, v.etat
         ORDER BY s.date_detection DESC'
    );
    $interventions = fetchAllRows(
        $pdo,
        'SELECT i.*, s.numero_serie, st.nom AS station_nom, o.nom_prenom
         FROM intervention i
         JOIN signalement s ON s.id_signalement = i.id_signalement
         JOIN station st ON st.id_station = s.id_station
         JOIN operateur o ON o.id_operateur = i.id_operateur
         ORDER BY i.date_fin IS NULL DESC, i.date_debut DESC'
    );
    ?>
    <div class="row g-4">
        <div class="col-xl-7">
            <section class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Maintenance</p><h2 class="h4 fw-bold mb-0">Signalements utilisateurs</h2></div>
                    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Détection</th><th>Vélo / station</th><th>Description</th><th>État</th><th></th></tr></thead><tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td class="small"><?= e(frenchDate($report['date_detection'])) ?><br><span class="text-secondary"><?= e($report['usager_nom']) ?></span></td>
                            <td><strong><?= e($report['numero_serie']) ?></strong><br><span class="small text-secondary"><?= e($report['station_nom']) ?></span></td>
                            <td class="small text-wrap-report"><?= e($report['description']) ?></td>
                            <td><?= statusBadge($report['etat_velo']) ?><br><span class="small text-secondary"><?= e((string) $report['nombre_interventions']) ?> intervention(s)</span></td>
                            <td>
                                <?php if (in_array($actor['role'], ['maintenance', 'administration'], true)): ?>
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#intervention-<?= e((string) $report['id_signalement']) ?>">Intervenir</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (in_array($actor['role'], ['maintenance', 'administration'], true)): ?>
                            <tr class="collapse" id="intervention-<?= e((string) $report['id_signalement']) ?>"><td colspan="5" class="bg-light">
                                <form method="post" action="index.php" class="row g-2 align-items-end p-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_intervention"><input type="hidden" name="id_signalement" value="<?= e((string) $report['id_signalement']) ?>">
                                    <div class="col-md-8"><label class="form-label small">Action prévue</label><input class="form-control form-control-sm" name="type_action" maxlength="120" placeholder="Ex. Diagnostic batterie" required></div>
                                    <div class="col-md-4"><button class="btn btn-sm btn-primary w-100" type="submit">Ouvrir l’intervention</button></div>
                                </form>
                            </td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($reports === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Aucun signalement.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Suivi</p><h2 class="h5 fw-bold mb-0">Interventions</h2></div>
                    <div class="list-group list-group-flush">
                    <?php foreach ($interventions as $intervention): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between gap-2"><strong><?= e($intervention['type_action']) ?></strong><?= statusBadge($intervention['date_fin'] === null ? 'en_cours' : 'terminee') ?></div>
                            <p class="small text-secondary mb-2"><?= e($intervention['numero_serie']) ?> · <?= e($intervention['station_nom']) ?><br><?= e($intervention['nom_prenom']) ?> · <?= e(frenchDate($intervention['date_debut'])) ?></p>
                            <?php if ($intervention['date_fin'] === null && (int) $intervention['id_operateur'] === (int) $actor['id']): ?>
                                <form method="post" action="index.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="finish_intervention"><input type="hidden" name="id_intervention" value="<?= e((string) $intervention['id_intervention']) ?>">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Clôturer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($interventions === []): ?><div class="list-group-item text-secondary">Aucune intervention.</div><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php
}

function renderFleetOperations(PDO $pdo, array $bikes, array $freeBoreRows): void
{
    $canManage = in_array((currentActor()['role'] ?? ''), ['maintenance', 'administration'], true);
    ?>
    <section class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-4 border-bottom"><p class="eyebrow mb-1">Parc de vélos</p><h2 class="h4 fw-bold mb-0">État et maintenance de la flotte</h2></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Vélo</th><th>Catégorie</th><th>Position</th><th>État</th><th>Batterie</th><th>Modification</th></tr></thead><tbody>
            <?php foreach ($bikes as $bike): ?>
                <tr>
                    <td><strong><?= e($bike['numero_serie']) ?></strong></td>
                    <td><?= e($bike['categorie']) ?></td>
                    <td><?= e($bike['station_nom'] ?? 'Hors station') ?><?= $bike['id_borne_actuelle'] ? ' · borne ' . e((string) $bike['id_borne_actuelle']) : '' ?></td>
                    <td><?= statusBadge($bike['etat']) ?></td>
                    <td><?= $bike['niveau_batterie'] === null ? '—' : e((string) $bike['niveau_batterie']) . '%' ?></td>
                    <td>
                    <?php if ($canManage): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#velo-<?= e($bike['numero_serie']) ?>">Modifier</button>
                    <?php else: ?>
                        <span class="small text-secondary">Lecture seule</span>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php if ($canManage): ?>
                    <tr class="collapse" id="velo-<?= e($bike['numero_serie']) ?>"><td colspan="6" class="bg-light">
                        <form method="post" action="index.php" class="row g-2 p-2 align-items-end">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_bike"><input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                            <div class="col-md-3"><label class="form-label small">État</label><select class="form-select form-select-sm" name="etat"><?php foreach (['disponible', 'maintenance', 'atelier', 'hors_service'] as $state): ?><option value="<?= e($state) ?>" <?= $state === $bike['etat'] ? 'selected' : '' ?>><?= e(statusLabel($state)) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label class="form-label small">Batterie</label><input class="form-control form-control-sm" type="number" min="0" max="100" name="niveau_batterie" value="<?= e((string) $bike['niveau_batterie']) ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Déplacer dans une borne libre</label><select class="form-select form-select-sm" name="id_borne_actuelle"><option value="">Conserver la borne actuelle</option><?php foreach ($freeBoreRows as $borne): ?><option value="<?= e((string) $borne['id_borne']) ?>"><?= e($borne['station_nom']) ?> · borne <?= e((string) $borne['id_borne']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-primary w-100" type="submit">Enregistrer</button></div>
                        </form>
                    </td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody></table></div>
        </div>
    </section>
<?php
}

function renderRegulationOperations(PDO $pdo, array $actor, array $stations, array $bikes): void
{
    $tours = fetchAllRows(
        $pdo,
        'SELECT t.*, o.nom_prenom FROM tournee_regulation t JOIN operateur o ON o.id_operateur = t.id_operateur ORDER BY t.date_tournee DESC, t.id_tournee DESC LIMIT 20'
    );
    $moves = fetchAllRows(
        $pdo,
        'SELECT m.*, s.nom AS station_nom, t.immatriculation_vehicule FROM mouvement_regulation m
         JOIN station s ON s.id_station = m.id_station
         JOIN tournee_regulation t ON t.id_tournee = m.id_tournee
         ORDER BY m.date_heure DESC LIMIT 20'
    );
    $canManage = in_array($actor['role'], ['regulation', 'administration'], true);
    ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Nouvelle tournée</p><h2 class="h5 fw-bold">Planifier une tournée</h2>
                    <?php if ($canManage): ?>
                    <form method="post" action="index.php" class="vstack gap-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_tournee">
                        <div><label class="form-label">Date</label><input class="form-control" type="date" name="date_tournee" value="<?= e(date('Y-m-d')) ?>" required></div>
                        <div><label class="form-label">Immatriculation véhicule</label><input class="form-control" name="immatriculation_vehicule" maxlength="20" placeholder="AB-123-CD" required></div>
                        <button class="btn btn-primary" type="submit">Créer la tournée</button>
                    </form>
                    <?php else: ?><p class="text-secondary mb-0">Votre rôle est en lecture seule pour la régulation.</p><?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Mouvement</p><h2 class="h5 fw-bold">Enregistrer un retrait ou un dépôt</h2>
                    <?php if ($canManage): ?>
                    <form method="post" action="index.php" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="add_movement">
                        <div class="col-md-4"><label class="form-label">Tournée</label><select class="form-select" name="id_tournee" required><option value="">Choisir…</option><?php foreach ($tours as $tour): ?><option value="<?= e((string) $tour['id_tournee']) ?>">#<?= e((string) $tour['id_tournee']) ?> · <?= e(frenchDate($tour['date_tournee'], false)) ?> · <?= e($tour['immatriculation_vehicule']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Nature</label><select class="form-select" name="nature"><option value="retrait">Retrait</option><option value="depot">Dépôt</option></select></div>
                        <div class="col-md-5"><label class="form-label">Vélo</label><select class="form-select" name="numero_serie" required><option value="">Choisir…</option><?php foreach ($bikes as $bike): ?><option value="<?= e($bike['numero_serie']) ?>"><?= e($bike['numero_serie']) ?> · <?= e($bike['etat']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-8"><label class="form-label">Station concernée</label><select class="form-select" name="id_station" required><option value="">Choisir…</option><?php foreach ($stations as $station): ?><option value="<?= e((string) $station['id_station']) ?>"><?= e($station['nom']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Enregistrer</button></div>
                    </form>
                    <?php else: ?><p class="text-secondary mb-0">Votre rôle est en lecture seule pour la régulation.</p><?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <section class="card border-0 shadow-sm mt-4"><div class="card-body p-0"><div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h5 fw-bold mb-0">Derniers mouvements enregistrés</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Tournée</th><th>Nature</th><th>Vélo</th><th>Station</th></tr></thead><tbody>
    <?php foreach ($moves as $move): ?><tr><td><?= e(frenchDate($move['date_heure'])) ?></td><td><?= e($move['immatriculation_vehicule']) ?></td><td><?= statusBadge($move['nature']) ?></td><td><strong><?= e($move['numero_serie']) ?></strong></td><td><?= e($move['station_nom']) ?></td></tr><?php endforeach; ?>
    <?php if ($moves === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Aucun mouvement.</td></tr><?php endif; ?>
    </tbody></table></div></div></section>
<?php
}

function renderAdministrationOperations(PDO $pdo, array $stations): void
{
    $formulas = fetchAllRows($pdo, 'SELECT * FROM formule ORDER BY nom');
    ?>
    <div class="row g-4">
        <div class="col-xl-7">
            <section class="card border-0 shadow-sm"><div class="card-body p-0"><div class="p-4 border-bottom"><p class="eyebrow mb-1">Réseau</p><h2 class="h4 fw-bold mb-0">Paramètres des stations</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Station</th><th>État</th><th>Seuil vélos</th><th>Seuil bornes</th><th></th></tr></thead><tbody>
            <?php foreach ($stations as $station): ?>
                <tr><form method="post" action="index.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_station"><input type="hidden" name="id_station" value="<?= e((string) $station['id_station']) ?>"><td><strong><?= e($station['nom']) ?></strong><br><span class="small text-secondary">Capacité <?= e((string) $station['capacite_bornes']) ?></span></td><td><select class="form-select form-select-sm" name="etat"><?php foreach (['en_service', 'hors_service', 'en_travaux'] as $state): ?><option value="<?= e($state) ?>" <?= $state === $station['etat'] ? 'selected' : '' ?>><?= e(statusLabel($state)) ?></option><?php endforeach; ?></select></td><td><input class="form-control form-control-sm" name="seuil_velos_min" type="number" min="0" max="<?= e((string) $station['capacite_bornes']) ?>" value="<?= e((string) $station['seuil_velos_min']) ?>"></td><td><input class="form-control form-control-sm" name="seuil_bornes_min" type="number" min="0" max="<?= e((string) $station['capacite_bornes']) ?>" value="<?= e((string) $station['seuil_bornes_min']) ?>"></td><td><button class="btn btn-sm btn-outline-primary" type="submit">Enregistrer</button></td></form></tr>
            <?php endforeach; ?>
            </tbody></table></div></div></section>
        </div>
        <div class="col-xl-5">
            <section class="card border-0 shadow-sm"><div class="card-body p-4"><p class="eyebrow mb-1">Tarification</p><h2 class="h5 fw-bold">Créer une formule</h2><form method="post" action="index.php" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_formula"><div class="col-12"><label class="form-label">Nom</label><input class="form-control" name="nom" maxlength="80" required></div><div class="col-md-6"><label class="form-label">Prix de base</label><input class="form-control" name="prix_base" type="number" step="0.01" min="0" value="0" required></div><div class="col-md-6"><label class="form-label">Durée incluse (min)</label><input class="form-control" name="duree_gratuite_minutes" type="number" min="0" value="30" required></div><div class="col-md-6"><label class="form-label">Tarif / 30 min</label><input class="form-control" name="tarif_par_tranche" type="number" step="0.01" min="0" value="0" required></div><div class="col-md-6"><label class="form-label">Supplément électrique</label><input class="form-control" name="supplement_electrique" type="number" step="0.01" min="0" value="0" required></div><div class="col-12"><button class="btn btn-primary" type="submit">Créer la formule</button></div></form></div></section>
            <section class="card border-0 shadow-sm mt-4"><div class="card-body p-4"><h3 class="h6 fw-bold">Formules existantes</h3><div class="vstack gap-2"><?php foreach ($formulas as $formula): ?><div class="small d-flex justify-content-between"><span><?= e($formula['nom']) ?></span><span><?= e(money($formula['prix_base'])) ?></span></div><?php endforeach; ?></div></div></section>
        </div>
    </div>
<?php
}

function renderStatisticsPage(PDO $pdo): void
{
    requireOperator();
    $dailyStats = fetchAllRows($pdo, 'SELECT * FROM v_statistiques_journalieres ORDER BY jour DESC LIMIT 14');
    $formulaStats = fetchAllRows($pdo, 'SELECT * FROM v_recettes_par_formule ORDER BY recettes DESC, formule');
    $topStations = fetchAllRows(
        $pdo,
        'SELECT s.nom, COUNT(t.id_trajet) AS nombre_trajets, COALESCE(SUM(t.cout_facture), 0) AS recettes
         FROM station s LEFT JOIN trajet t ON t.id_station_depart = s.id_station AND t.date_arrivee IS NOT NULL
         GROUP BY s.id_station, s.nom ORDER BY nombre_trajets DESC, s.nom'
    );
    $totalRevenue = (float) fetchValue($pdo, 'SELECT COALESCE(SUM(cout_facture), 0) FROM trajet WHERE date_arrivee IS NOT NULL');
    $completedTrips = (int) fetchValue($pdo, 'SELECT COUNT(*) FROM trajet WHERE date_arrivee IS NOT NULL');
    $averageDuration = (float) fetchValue($pdo, 'SELECT COALESCE(AVG(duree_minutes), 0) FROM trajet WHERE date_arrivee IS NOT NULL');
    $chartStats = array_reverse($dailyStats);
    $labels = array_map(static fn (array $stat): string => frenchDate($stat['jour'], false), $chartStats);
    $values = array_map(static fn (array $stat): float => (float) $stat['nombre_trajets'], $chartStats);

    renderHeader('Statistiques', 'statistiques');
    renderPageTitle('Pilotage', 'Statistiques du service', 'Indicateurs calculés directement à partir des vues de reporting MySQL.');
    ?>
    <div class="row g-3 g-lg-4 mb-4">
        <?php renderMetric('Trajets terminés', $completedTrips, 'Depuis le début du jeu de données', 'primary'); ?>
        <?php renderMetric('Recettes', money($totalRevenue), 'Trajets facturés', 'success'); ?>
        <?php renderMetric('Durée moyenne', number_format($averageDuration, 1, ',', ' ') . ' min', 'Sur les trajets terminés', 'info'); ?>
        <?php renderMetric('Formules', count($formulaStats), 'Offres tarifaires suivies', 'secondary'); ?>
    </div>
    <div class="row g-4">
        <div class="col-xl-7"><section class="card border-0 shadow-sm h-100"><div class="card-body p-4"><p class="eyebrow mb-1">Activité</p><h2 class="h5 fw-bold">Trajets par jour</h2><div class="chart-container"><canvas id="dailyTripsChart"></canvas></div></div></section></div>
        <div class="col-xl-5"><section class="card border-0 shadow-sm h-100"><div class="card-body p-0"><div class="p-4 border-bottom"><p class="eyebrow mb-1">Recettes</p><h2 class="h5 fw-bold mb-0">Par formule</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Formule</th><th>Trajets</th><th class="text-end">Recettes</th></tr></thead><tbody><?php foreach ($formulaStats as $formula): ?><tr><td><?= e($formula['formule']) ?></td><td><?= e((string) $formula['nombre_trajets']) ?></td><td class="text-end"><?= e(money($formula['recettes'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div></section></div>
        <div class="col-12"><section class="card border-0 shadow-sm"><div class="card-body p-0"><div class="p-4 border-bottom"><p class="eyebrow mb-1">Utilisation</p><h2 class="h5 fw-bold mb-0">Départs par station</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Station</th><th>Trajets terminés</th><th class="text-end">Recettes associées</th></tr></thead><tbody><?php foreach ($topStations as $station): ?><tr><td><strong><?= e($station['nom']) ?></strong></td><td><?= e((string) $station['nombre_trajets']) ?></td><td class="text-end"><?= e(money($station['recettes'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div></section></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    (() => {
        const canvas = document.getElementById('dailyTripsChart');
        if (!canvas || typeof Chart === 'undefined') return;
        new Chart(canvas, {
            type: 'line',
            data: { labels: <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, datasets: [{ label: 'Trajets', data: <?= json_encode($values) ?>, borderColor: '#2363eb', backgroundColor: 'rgba(35,99,235,.12)', tension: .35, fill: true, borderWidth: 3, pointRadius: 4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    })();
    </script>
<?php
    renderFooter();
}
