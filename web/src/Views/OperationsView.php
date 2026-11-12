<?php

declare(strict_types=1);

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
