<?php

declare(strict_types=1);

function renderStationsPage(PDO $pdo): void
{
    $stations = stationData($pdo);
    $bikes = bikesByStation($pdo);
    $openTrip = null;
    if (isUsager()) {
        $actor = requireUsager();
        $openTrip = fetchOne($pdo, 'SELECT id_trajet FROM trajet WHERE id_usager = :id AND date_arrivee IS NULL', ['id' => $actor['id']]);
    }

    renderHeader('Stations', 'stations');
    renderPageTitle('Réseau en direct', 'Stations et vélos disponibles', 'Recherchez une station, réservez un vélo pendant 15 minutes ou démarrez un trajet.');
    ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <label class="visually-hidden" for="station-search">Rechercher une station</label>
            <input class="form-control form-control-lg" id="station-search" type="search" placeholder="Rechercher une station ou une adresse…" data-station-search>
        </div>
    </div>
    <div class="row g-4" data-station-list>
        <?php foreach ($stations as $station): ?>
            <div class="col-lg-6 station-item" data-search="<?= e(mb_strtolower($station['nom'] . ' ' . $station['etat'])) ?>">
                <article class="card border-0 shadow-sm h-100 station-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-3 align-items-start">
                            <div>
                                <p class="eyebrow mb-1">Station #<?= e((string) $station['id_station']) ?></p>
                                <h2 class="h4 fw-bold mb-1"><?= e($station['nom']) ?></h2>
                                <p class="text-secondary small mb-0">État : <?= e(statusLabel($station['etat'])) ?></p>
                            </div>
                            <div class="text-end">
                                <?= statusBadge($station['statut_alerte']) ?>
                                <a class="d-block small mt-2" target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat=<?= e((string) $station['latitude'] ?? '') ?>&amp;mlon=<?= e((string) $station['longitude'] ?? '') ?>#map=17/<?= e((string) $station['latitude'] ?? '') ?>/<?= e((string) $station['longitude'] ?? '') ?>">Localiser</a>
                            </div>
                        </div>
                        <div class="row g-2 my-3 text-center">
                            <div class="col-4"><div class="station-count"><strong><?= e((string) $station['velos_disponibles']) ?></strong><span>vélos</span></div></div>
                            <div class="col-4"><div class="station-count"><strong><?= e((string) $station['velos_mecaniques_disponibles']) ?></strong><span>mécaniques</span></div></div>
                            <div class="col-4"><div class="station-count"><strong><?= e((string) $station['velos_electriques_disponibles']) ?></strong><span>électriques</span></div></div>
                        </div>
                        <p class="small text-secondary mb-3"><?= e((string) $station['bornes_libres']) ?> borne(s) libre(s) sur <?= e((string) $station['nombre_bornes']) ?>.</p>
                        <div class="vstack gap-2">
                            <?php $stationBikes = $bikes[(int) $station['id_station']] ?? []; ?>
                            <?php if ($stationBikes === []): ?>
                                <p class="small text-secondary mb-0">Aucun vélo actuellement rattaché à cette station.</p>
                            <?php endif; ?>
                            <?php foreach ($stationBikes as $bike): ?>
                                <div class="bike-row rounded-3 p-3 <?= $bike['etat'] === 'disponible' ? 'bike-row-ready' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong><?= e($bike['numero_serie']) ?></strong>
                                            <span class="text-secondary small">· <?= e($bike['categorie']) ?> · borne <?= e((string) $bike['id_borne_actuelle']) ?></span>
                                            <?php if ($bike['niveau_batterie'] !== null): ?>
                                                <span class="text-secondary small">· batterie <?= e((string) $bike['niveau_batterie']) ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                        <?= statusBadge($bike['etat']) ?>
                                    </div>
                                    <?php if (isUsager()): ?>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <?php if ($bike['etat'] === 'disponible' && $openTrip === null): ?>
                                                <form method="post" action="index.php">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="start_trip">
                                                    <input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                                                    <input type="hidden" name="id_station_depart" value="<?= e((string) $station['id_station']) ?>">
                                                    <input type="hidden" name="id_borne_depart" value="<?= e((string) $bike['id_borne_actuelle']) ?>">
                                                    <button class="btn btn-sm btn-primary" type="submit">Emprunter</button>
                                                </form>
                                                <form method="post" action="index.php">
                                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                    <input type="hidden" name="action" value="reserve_bike">
                                                    <input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                                                    <input type="hidden" name="return_page" value="stations">
                                                    <button class="btn btn-sm btn-outline-primary" type="submit">Réserver 15 min</button>
                                                </form>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#report-<?= e($bike['numero_serie']) ?>">Signaler</button>
                                        </div>
                                        <div class="collapse mt-2" id="report-<?= e($bike['numero_serie']) ?>">
                                            <form method="post" action="index.php" class="border-top pt-2">
                                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="report_issue">
                                                <input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                                                <input type="hidden" name="id_station" value="<?= e((string) $station['id_station']) ?>">
                                                <input type="hidden" name="return_page" value="stations">
                                                <label class="form-label small" for="description-<?= e($bike['numero_serie']) ?>">Décrivez le problème</label>
                                                <div class="input-group">
                                                    <input class="form-control form-control-sm" id="description-<?= e($bike['numero_serie']) ?>" name="description" maxlength="2000" required>
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Envoyer</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php if ($bike['etat'] === 'disponible' && $openTrip !== null): ?>
                                        <p class="small text-secondary mt-2 mb-0">Restituez votre trajet en cours avant d’emprunter un autre vélo.</p>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
<?php
    renderFooter();
}
