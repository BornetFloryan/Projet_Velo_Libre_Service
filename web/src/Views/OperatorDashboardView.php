<?php

declare(strict_types=1);

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
