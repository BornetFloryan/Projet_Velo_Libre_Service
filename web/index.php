<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/layout.php';
require __DIR__ . '/app/actions.php';
require __DIR__ . '/app/views.php';

if ($databaseError !== null || $pdo === null) {
    renderDatabaseError();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePost($pdo);
}

$page = (string) ($_GET['page'] ?? (isAuthenticated() ? 'dashboard' : 'login'));
$publicPages = ['login', 'register'];
$privatePages = ['dashboard', 'stations', 'trajets', 'compte', 'operations', 'statistiques'];

if (!in_array($page, [...$publicPages, ...$privatePages], true)) {
    $page = isAuthenticated() ? 'dashboard' : 'login';
}

if (!isAuthenticated() && !in_array($page, $publicPages, true)) {
    redirectTo('login');
}

if (isAuthenticated() && in_array($page, $publicPages, true)) {
    redirectTo('dashboard');
}

if (isOperator() && in_array($page, ['trajets', 'compte'], true)) {
    redirectTo('dashboard');
}

if (isUsager() && in_array($page, ['operations', 'statistiques'], true)) {
    redirectTo('dashboard');
}

switch ($page) {
    case 'login':
        renderLoginPage(false);
        break;
    case 'register':
        renderLoginPage(true);
        break;
    case 'stations':
        renderStationsPage($pdo);
        break;
    case 'trajets':
        renderTripsPage($pdo);
        break;
    case 'compte':
        renderAccountPage($pdo);
        break;
    case 'operations':
        renderOperationsPage($pdo);
        break;
    case 'statistiques':
        renderStatisticsPage($pdo);
        break;
    default:
        renderDashboard($pdo);
        break;
}
