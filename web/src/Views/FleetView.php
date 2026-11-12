<?php

declare(strict_types=1);

function renderFleetOperations(PDO $pdo, array $bikes, array $freeBoreRows): void
{
    $canManage = in_array((currentActor()['role'] ?? ''), ['maintenance', 'administration'], true);
    ?>
    <section class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="p-4 border-bottom"><p class="eyebrow mb-1">Parc de vélos</p><h2 class="h4 fw-bold mb-0">État et maintenance de la flotte</h2></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Vélo</th><th>Catégorie</th><th>Position</th><th>État</th><th>Batterie</th><th>Modification</th></tr></thead><tbody>
            <?php foreach ($bikes as $bike): ?>
                <tr>
                    <td><strong><?= e($bike['numero_serie']) ?></strong></td>
                    <td><?= e($bike['categorie']) ?></td>
                    <td><?= e($bike['station_nom'] ?? 'Hors station') ?><?= $bike['id_borne_actuelle'] ? ' · borne ' . e((string) $bike['id_borne_actuelle']) : '' ?></td>
                    <td><?= statusBadge($bike['etat']) ?></td>
                    <td><?= $bike['niveau_batterie'] === null ? '—' : e((string) $bike['niveau_batterie']) . '%' ?></td>
                    <td>
                    <?php if ($canManage): ?>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#velo-<?= e($bike['numero_serie']) ?>">Modifier</button>
                    <?php else: ?>
                        <span class="small text-secondary">Lecture seule</span>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php if ($canManage): ?>
                    <tr class="collapse" id="velo-<?= e($bike['numero_serie']) ?>"><td colspan="6" class="bg-light">
                        <form method="post" action="index.php" class="row g-2 p-2 align-items-end">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_bike"><input type="hidden" name="numero_serie" value="<?= e($bike['numero_serie']) ?>">
                            <div class="col-md-3"><label class="form-label small">État</label><select class="form-select form-select-sm" name="etat"><?php foreach (['disponible', 'maintenance', 'atelier', 'hors_service'] as $state): ?><option value="<?= e($state) ?>" <?= $state === $bike['etat'] ? 'selected' : '' ?>><?= e(statusLabel($state)) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-2"><label class="form-label small">Batterie</label><input class="form-control form-control-sm" type="number" min="0" max="100" name="niveau_batterie" value="<?= e((string) $bike['niveau_batterie']) ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Déplacer dans une borne libre</label><select class="form-select form-select-sm" name="id_borne_actuelle"><option value="">Conserver la borne actuelle</option><?php foreach ($freeBoreRows as $borne): ?><option value="<?= e((string) $borne['id_borne']) ?>"><?= e($borne['station_nom']) ?> · borne <?= e((string) $borne['id_borne']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><button class="btn btn-sm btn-primary w-100" type="submit">Enregistrer</button></div>
                        </form>
                    </td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody></table></div>
        </div>
    </section>
<?php
}
