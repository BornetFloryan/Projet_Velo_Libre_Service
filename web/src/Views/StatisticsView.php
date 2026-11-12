<?php

declare(strict_types=1);

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
