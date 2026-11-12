<?php

declare(strict_types=1);

function navigationLink(string $page, string $label, string $currentPage): string
{
    $active = $page === $currentPage ? ' active' : '';
    return '<a class="nav-link' . $active . '" href="' . e(appUrl($page)) . '">' . e($label) . '</a>';
}

function renderHeader(string $title, string $currentPage, bool $withNavigation = true): void
{
    $actor = currentActor();
    $fullTitle = e($title . ' · ' . APP_NAME);
    ?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $fullTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<?php if ($withNavigation): ?>
    <nav class="navbar navbar-expand-lg navbar-dark app-navbar sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= e(appUrl('dashboard')) ?>">
                <span class="brand-mark">V</span> VéloLibre
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-label="Ouvrir la navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <div class="navbar-nav me-auto">
                    <?= navigationLink('dashboard', 'Tableau de bord', $currentPage) ?>
                    <?= navigationLink('stations', 'Stations', $currentPage) ?>
                    <?php if (isUsager()): ?>
                        <?= navigationLink('trajets', 'Mes trajets', $currentPage) ?>
                        <?= navigationLink('compte', 'Mon compte', $currentPage) ?>
                    <?php else: ?>
                        <?= navigationLink('operations', 'Opérations', $currentPage) ?>
                        <?= navigationLink('statistiques', 'Statistiques', $currentPage) ?>
                    <?php endif; ?>
                </div>
                <div class="navbar-nav align-items-lg-center">
                    <span class="navbar-text me-lg-3 small text-white-50">
                        <?= e($actor['name'] ?? '') ?>
                        <?php if (isOperator()): ?>
                            · <?= e(ucfirst((string) $actor['role'])) ?>
                        <?php endif; ?>
                    </span>
                    <form method="post" action="index.php" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="logout">
                        <button class="btn btn-outline-light btn-sm" type="submit">Déconnexion</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>
<main class="<?= $withNavigation ? 'py-4 py-lg-5' : '' ?>">
    <div class="container">
        <?php foreach (consumeFlashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endforeach; ?>
<?php
}

function renderFooter(): void
{
    ?>
    </div>
</main>
<footer class="py-4 mt-auto border-top bg-white">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 small text-secondary">
        <span>Projet BD70 · UTBM · Vélo libre-service</span>
        <span>Interface de démonstration connectée à MySQL</span>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

function renderPageTitle(string $eyebrow, string $title, string $subtitle = ''): void
{
    ?>
    <div class="mb-4 mb-lg-5">
        <p class="eyebrow mb-2"><?= e($eyebrow) ?></p>
        <h1 class="display-6 fw-bold mb-2"><?= e($title) ?></h1>
        <?php if ($subtitle !== ''): ?>
            <p class="lead text-secondary mb-0"><?= e($subtitle) ?></p>
        <?php endif; ?>
    </div>
<?php
}

function statusClass(string $status): string
{
    return match ($status) {
        'disponible', 'en_service', 'normale', 'payee', 'utilisee', 'terminee' => 'success',
        'reserve', 'active', 'maintenance', 'en_cours', 'en_attente' => 'warning',
        'hors_service', 'atelier', 'en_travaux', 'saturee', 'expiree', 'annulee' => 'secondary',
        'vide' => 'danger',
        default => 'primary',
    };
}

function statusLabel(string $status): string
{
    return str_replace('_', ' ', $status);
}

function statusBadge(?string $status): string
{
    if ($status === null || $status === '') {
        return '<span class="text-secondary">—</span>';
    }

    return '<span class="badge rounded-pill text-bg-' . e(statusClass($status)) . ' text-capitalize">' . e(statusLabel($status)) . '</span>';
}
