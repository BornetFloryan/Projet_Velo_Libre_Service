<?php

declare(strict_types=1);

final class Router
{
    public function dispatch(PDO $pdo): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            handlePost($pdo);
        }

        $page = (string) ($_GET['page'] ?? (isAuthenticated() ? 'dashboard' : 'login'));
        $public = ['login', 'register'];
        $private = ['dashboard', 'stations', 'trajets', 'compte', 'operations', 'statistiques'];

        if (!in_array($page, [...$public, ...$private], true)) {
            $page = isAuthenticated() ? 'dashboard' : 'login';
        }
        if (!isAuthenticated() && !in_array($page, $public, true)) redirectTo('login');
        if (isAuthenticated() && in_array($page, $public, true)) redirectTo('dashboard');
        if (isOperator() && in_array($page, ['trajets', 'compte'], true)) redirectTo('dashboard');
        if (isUsager() && in_array($page, ['operations', 'statistiques'], true)) redirectTo('dashboard');

        (new PageController())->show($page, $pdo);
    }
}
