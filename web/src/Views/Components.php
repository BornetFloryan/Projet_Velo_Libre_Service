<?php

declare(strict_types=1);

function renderMetric(string $label, string|int|float $value, string $hint, string $accent = 'primary'): void
{
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card h-100 border-0 shadow-sm metric-<?= e($accent) ?>">
            <div class="card-body">
                <p class="text-uppercase small fw-semibold text-secondary mb-2"><?= e($label) ?></p>
                <p class="display-6 fw-bold mb-1"><?= e((string) $value) ?></p>
                <p class="mb-0 small text-secondary"><?= e($hint) ?></p>
            </div>
        </div>
    </div>
<?php
}
