<?php

declare(strict_types=1);

function renderReturnForm(PDO $pdo, array $openTrip): void
{
    $bores = freeBores($pdo);
    if ($bores === []) {
        echo '<div class="alert alert-warning mb-0">Aucune borne libre n’est disponible pour le moment.</div>';
        return;
    }
    ?>
    <form method="post" action="index.php" class="row g-3 mt-1" data-return-form>
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="action" value="return_trip">
        <input type="hidden" name="id_trajet" value="<?= e((string) $openTrip['id_trajet']) ?>">
        <div class="col-md-5">
            <label class="form-label" for="station-arrivee">Station de restitution</label>
            <select class="form-select" id="station-arrivee" name="id_station_arrivee" required data-station-select>
                <option value="">Choisir…</option>
                <?php foreach (stationData($pdo) as $station): ?>
                    <option value="<?= e((string) $station['id_station']) ?>"><?= e($station['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="borne-arrivee">Borne libre</label>
            <select class="form-select" id="borne-arrivee" name="id_borne_arrivee" required data-borne-select>
                <option value="">Choisir…</option>
                <?php foreach ($bores as $borne): ?>
                    <option value="<?= e((string) $borne['id_borne']) ?>" data-station="<?= e((string) $borne['id_station']) ?>">
                        <?= e($borne['station_nom']) ?> · borne <?= e((string) $borne['id_borne']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-success w-100" type="submit">Restituer</button>
        </div>
    </form>
<?php
}
