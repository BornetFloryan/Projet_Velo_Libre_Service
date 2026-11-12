<?php

declare(strict_types=1);

function renderRegulationOperations(PDO $pdo, array $actor, array $stations, array $bikes): void
{
    $tours = fetchAllRows(
        $pdo,
        'SELECT t.*, o.nom_prenom FROM tournee_regulation t JOIN operateur o ON o.id_operateur = t.id_operateur ORDER BY t.date_tournee DESC, t.id_tournee DESC LIMIT 20'
    );
    $moves = fetchAllRows(
        $pdo,
        'SELECT m.*, s.nom AS station_nom, t.immatriculation_vehicule FROM mouvement_regulation m
         JOIN station s ON s.id_station = m.id_station
         JOIN tournee_regulation t ON t.id_tournee = m.id_tournee
         ORDER BY m.date_heure DESC LIMIT 20'
    );
    $canManage = in_array($actor['role'], ['regulation', 'administration'], true);
    ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Nouvelle tournée</p><h2 class="h5 fw-bold">Planifier une tournée</h2>
                    <?php if ($canManage): ?>
                    <form method="post" action="index.php" class="vstack gap-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="create_tournee">
                        <div><label class="form-label">Date</label><input class="form-control" type="date" name="date_tournee" value="<?= e(date('Y-m-d')) ?>" required></div>
                        <div><label class="form-label">Immatriculation véhicule</label><input class="form-control" name="immatriculation_vehicule" maxlength="20" placeholder="AB-123-CD" required></div>
                        <button class="btn btn-primary" type="submit">Créer la tournée</button>
                    </form>
                    <?php else: ?><p class="text-secondary mb-0">Votre rôle est en lecture seule pour la régulation.</p><?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Mouvement</p><h2 class="h5 fw-bold">Enregistrer un retrait ou un dépôt</h2>
                    <?php if ($canManage): ?>
                    <form method="post" action="index.php" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="action" value="add_movement">
                        <div class="col-md-4"><label class="form-label">Tournée</label><select class="form-select" name="id_tournee" required><option value="">Choisir…</option><?php foreach ($tours as $tour): ?><option value="<?= e((string) $tour['id_tournee']) ?>">#<?= e((string) $tour['id_tournee']) ?> · <?= e(frenchDate($tour['date_tournee'], false)) ?> · <?= e($tour['immatriculation_vehicule']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><label class="form-label">Nature</label><select class="form-select" name="nature"><option value="retrait">Retrait</option><option value="depot">Dépôt</option></select></div>
                        <div class="col-md-5"><label class="form-label">Vélo</label><select class="form-select" name="numero_serie" required><option value="">Choisir…</option><?php foreach ($bikes as $bike): ?><option value="<?= e($bike['numero_serie']) ?>"><?= e($bike['numero_serie']) ?> · <?= e($bike['etat']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-8"><label class="form-label">Station concernée</label><select class="form-select" name="id_station" required><option value="">Choisir…</option><?php foreach ($stations as $station): ?><option value="<?= e((string) $station['id_station']) ?>"><?= e($station['nom']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">Enregistrer</button></div>
                    </form>
                    <?php else: ?><p class="text-secondary mb-0">Votre rôle est en lecture seule pour la régulation.</p><?php endif; ?>
                </div>
            </section>
        </div>
    </div>
    <section class="card border-0 shadow-sm mt-4"><div class="card-body p-0"><div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h5 fw-bold mb-0">Derniers mouvements enregistrés</h2></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Date</th><th>Tournée</th><th>Nature</th><th>Vélo</th><th>Station</th></tr></thead><tbody>
    <?php foreach ($moves as $move): ?><tr><td><?= e(frenchDate($move['date_heure'])) ?></td><td><?= e($move['immatriculation_vehicule']) ?></td><td><?= statusBadge($move['nature']) ?></td><td><strong><?= e($move['numero_serie']) ?></strong></td><td><?= e($move['station_nom']) ?></td></tr><?php endforeach; ?>
    <?php if ($moves === []): ?><tr><td colspan="5" class="text-center text-secondary py-4">Aucun mouvement.</td></tr><?php endif; ?>
    </tbody></table></div></div></section>
<?php
}
