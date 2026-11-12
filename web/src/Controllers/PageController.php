<?php

declare(strict_types=1);

final class PageController
{
    public function show(string $page, PDO $pdo): void
    {
        match ($page) {
            'login' => renderLoginPage(false),
            'register' => renderLoginPage(true),
            'stations' => renderStationsPage($pdo),
            'trajets' => renderTripsPage($pdo),
            'compte' => renderAccountPage($pdo),
            'operations' => renderOperationsPage($pdo),
            'statistiques' => renderStatisticsPage($pdo),
            default => renderDashboard($pdo),
        };
    }
}
