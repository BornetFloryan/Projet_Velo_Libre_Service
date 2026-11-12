<?php

declare(strict_types=1);

function renderAdministrationOperations(PDO $pdo, array $stations): void
{
    $formulas = fetchAllRows($pdo, 'SELECT * FROM formule ORDER BY nom');
    ?>
    <div class="row g-4">
        <div class="col-xl-7">
            <section class="card border-0 shadow-sm"><div class="card-body p-0"><div class="p-4 border-bottom"><p class="eyebrow mb-1">Réseau</p><h2 class="h4 fw-bold mb-0">Paramètres des stations</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Station</th><th>État</th><th>Seuil vélos</th><th>Seuil bornes</th><th></th></tr></thead><tbody>
            <?php foreach ($stations as $station): ?>
                <tr><form method="post" action="index.php"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="update_station"><input type="hidden" name="id_station" value="<?= e((string) $station['id_station']) ?>"><td><strong><?= e($station['nom']) ?></strong><br><span class="small text-secondary">Capacité <?= e((string) $station['capacite_bornes']) ?></span></td><td><select class="form-select form-select-sm" name="etat"><?php foreach (['en_service', 'hors_service', 'en_travaux'] as $state): ?><option value="<?= e($state) ?>" <?= $state === $station['etat'] ? 'selected' : '' ?>><?= e(statusLabel($state)) ?></option><?php endforeach; ?></select></td><td><input class="form-control form-control-sm" name="seuil_velos_min" type="number" min="0" max="<?= e((string) $station['capacite_bornes']) ?>" value="<?= e((string) $station['seuil_velos_min']) ?>"></td><td><input class="form-control form-control-sm" name="seuil_bornes_min" type="number" min="0" max="<?= e((string) $station['capacite_bornes']) ?>" value="<?= e((string) $station['seuil_bornes_min']) ?>"></td><td><button class="btn btn-sm btn-outline-primary" type="submit">Enregistrer</button></td></form></tr>
            <?php endforeach; ?>
            </tbody></table></div></div></section>
        </div>
        <div class="col-xl-5">
            <section class="card border-0 shadow-sm"><div class="card-body p-4"><p class="eyebrow mb-1">Tarification</p><h2 class="h5 fw-bold">Créer une formule</h2><form method="post" action="index.php" class="row g-3"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_formula"><div class="col-12"><label class="form-label">Nom</label><input class="form-control" name="nom" maxlength="80" required></div><div class="col-md-6"><label class="form-label">Prix de base</label><input class="form-control" name="prix_base" type="number" step="0.01" min="0" value="0" required></div><div class="col-md-6"><label class="form-label">Durée incluse (min)</label><input class="form-control" name="duree_gratuite_minutes" type="number" min="0" value="30" required></div><div class="col-md-6"><label class="form-label">Tarif / 30 min</label><input class="form-control" name="tarif_par_tranche" type="number" step="0.01" min="0" value="0" required></div><div class="col-md-6"><label class="form-label">Supplément électrique</label><input class="form-control" name="supplement_electrique" type="number" step="0.01" min="0" value="0" required></div><div class="col-12"><button class="btn btn-primary" type="submit">Créer la formule</button></div></form></div></section>
            <section class="card border-0 shadow-sm mt-4"><div class="card-body p-4"><h3 class="h6 fw-bold">Formules existantes</h3><div class="vstack gap-2"><?php foreach ($formulas as $formula): ?><div class="small d-flex justify-content-between"><span><?= e($formula['nom']) ?></span><span><?= e(money($formula['prix_base'])) ?></span></div><?php endforeach; ?></div></div></section>
        </div>
    </div>
<?php
}
