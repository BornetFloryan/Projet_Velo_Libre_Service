<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/bootstrap.php';
require_once __DIR__ . '/Support/layout.php';
require_once __DIR__ . '/Actions/ActionDispatcher.php';
require_once __DIR__ . '/Views/ViewRegistry.php';
require_once __DIR__ . '/Controllers/PageController.php';
require_once __DIR__ . '/Http/Router.php';

final class Application
{
    public function run(): void
    {
        global $databaseError, $pdo;
        if ($databaseError !== null || $pdo === null) {
            renderDatabaseError();
            return;
        }
        (new Router())->dispatch($pdo);
    }
}
