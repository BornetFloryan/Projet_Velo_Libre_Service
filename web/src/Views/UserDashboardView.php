<?php

declare(strict_types=1);

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
