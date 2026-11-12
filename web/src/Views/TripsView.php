<?php

declare(strict_types=1);

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
