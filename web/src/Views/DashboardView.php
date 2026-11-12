<?php

declare(strict_types=1);

function renderDashboard(PDO $pdo): void
{
    if (isUsager()) {
        renderUserDashboard($pdo);
        return;
    }

    renderOperatorDashboard($pdo);
}
