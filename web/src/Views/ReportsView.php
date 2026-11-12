<?php

declare(strict_types=1);

function renderReportsOperations(PDO $pdo, array $actor): void
{
    $reports = fetchAllRows(
        $pdo,
        'SELECT s.id_signalement, s.date_detection, s.description, s.numero_serie,
                st.nom AS station_nom, CONCAT(u.prenom, " ", u.nom) AS usager_nom,
                v.etat AS etat_velo,
                COUNT(i.id_intervention) AS nombre_interventions
         FROM signalement s
         JOIN station st ON st.id_station = s.id_station
         JOIN usager u ON u.id_usager = s.id_usager
         JOIN velo v ON v.numero_serie = s.numero_serie
         LEFT JOIN intervention i ON i.id_signalement = s.id_signalement
         GROUP BY s.id_signalement, s.date_detection, s.description, s.numero_serie, st.nom, u.prenom, u.nom, v.etat
         ORDER BY s.date_detection DESC'
    );
    $interventions = fetchAllRows(
        $pdo,
        'SELECT i.*, s.numero_serie, st.nom AS station_nom, o.nom_prenom
         FROM intervention i
         JOIN signalement s ON s.id_signalement = i.id_signalement
         JOIN station st ON st.id_station = s.id_station
         JOIN operateur o ON o.id_operateur = i.id_operateur
         ORDER BY i.date_fin IS NULL DESC, i.date_debut DESC'
    );
    ?>
    <div class="row g-4">
        <div class="col-xl-7">
            <section class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Maintenance</p><h2 class="h4 fw-bold mb-0">Signalements utilisateurs</h2></div>
                    <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Détection</th><th>Vélo / station</th><th>Description</th><th>État</th><th></th></tr></thead><tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td class="small"><?= e(frenchDate($report['date_detection'])) ?><br><span class="text-secondary"><?= e($report['usager_nom']) ?></span></td>
                            <td><strong><?= e($report['numero_serie']) ?></strong><br><span class="small text-secondary"><?= e($report['station_nom']) ?></span></td>
                            <td class="small text-wrap-report"><?= e($report['description']) ?></td>
                            <td><?= statusBadge($report['etat_velo']) ?><br><span class="small text-secondary"><?= e((string) $report['nombre_interventions']) ?> intervention(s)</span></td>
                            <td>
                                <?php if (in_array($actor['role'], ['maintenance', 'administration'], true)): ?>
                                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#intervention-<?= e((string) $report['id_signalement']) ?>">Intervenir</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (in_array($actor['role'], ['maintenance', 'administration'], true)): ?>
                            <tr class="collapse" id="intervention-<?= e((string) $report['id_signalement']) ?>"><td colspan="5" class="bg-light">
                                <form method="post" action="index.php" class="row g-2 align-items-end p-2">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_intervention"><input type="hidden" name="id_signalement" value="<?= e((string) $report['id_signalement']) ?>">
                                    <div class="col-md-8"><label class="form-label small">Action prévue</label><input class="form-control form-control-sm" name="type_action" maxlength="120" placeholder="Ex. Diagnostic batterie" required></div>
                                    <div class="col-md-4"><button class="btn btn-sm btn-primary w-100" type="submit">Ouvrir l’intervention</button></div>
                                </form>
                            </td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($reports === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Aucun signalement.</td></tr><?php endif; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-xl-5">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Suivi</p><h2 class="h5 fw-bold mb-0">Interventions</h2></div>
                    <div class="list-group list-group-flush">
                    <?php foreach ($interventions as $intervention): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between gap-2"><strong><?= e($intervention['type_action']) ?></strong><?= statusBadge($intervention['date_fin'] === null ? 'en_cours' : 'terminee') ?></div>
                            <p class="small text-secondary mb-2"><?= e($intervention['numero_serie']) ?> · <?= e($intervention['station_nom']) ?><br><?= e($intervention['nom_prenom']) ?> · <?= e(frenchDate($intervention['date_debut'])) ?></p>
                            <?php if ($intervention['date_fin'] === null && (int) $intervention['id_operateur'] === (int) $actor['id']): ?>
                                <form method="post" action="index.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="finish_intervention"><input type="hidden" name="id_intervention" value="<?= e((string) $intervention['id_intervention']) ?>">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Clôturer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($interventions === []): ?><div class="list-group-item text-secondary">Aucune intervention.</div><?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
<?php
}
