<?php

declare(strict_types=1);

function renderAccountPage(PDO $pdo): void
{
    $actor = requireUsager();
    $user = fetchOne($pdo, 'SELECT * FROM usager WHERE id_usager = :id', ['id' => $actor['id']]);
    $subscriptions = fetchAllRows(
        $pdo,
        'SELECT a.*, f.nom AS formule_nom, f.prix_base, f.duree_gratuite_minutes, f.tarif_par_tranche, f.supplement_electrique
         FROM abonnement a JOIN formule f ON f.id_formule = a.id_formule
         WHERE a.id_usager = :id ORDER BY a.date_fin DESC',
        ['id' => $actor['id']]
    );
    $operations = fetchAllRows(
        $pdo,
        'SELECT o.*, t.numero_serie FROM operation_portefeuille o
         LEFT JOIN trajet t ON t.id_trajet = o.id_trajet
         WHERE o.id_usager = :id ORDER BY o.date_heure DESC LIMIT 15',
        ['id' => $actor['id']]
    );
    $formulas = fetchAllRows($pdo, 'SELECT * FROM formule ORDER BY prix_base, nom');

    renderHeader('Mon compte', 'compte');
    renderPageTitle('Espace personnel', 'Mon compte', 'Gérez votre profil, vos abonnements et votre portefeuille.');
    ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <section class="card border-0 shadow-sm h-100 wallet-card">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Portefeuille</p>
                    <p class="display-5 fw-bold mb-1"><?= e(money($user['solde_portefeuille'])) ?></p>
                    <p class="text-secondary">Solde utilisable lors de la restitution.</p>
                    <form method="post" action="index.php" class="mt-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="recharge_wallet">
                        <div class="input-group">
                            <input class="form-control" type="number" name="montant" min="1" max="200" step="0.01" value="10" required aria-label="Montant à recharger">
                            <button class="btn btn-primary" type="submit">Recharger</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <p class="eyebrow mb-1">Profil</p>
                    <form method="post" action="index.php" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="col-md-4"><label class="form-label">Prénom</label><input class="form-control" name="prenom" maxlength="80" value="<?= e($user['prenom']) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Nom</label><input class="form-control" name="nom" maxlength="80" value="<?= e($user['nom']) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">E-mail</label><input class="form-control" name="email" type="email" maxlength="255" value="<?= e($user['email']) ?>" required></div>
                        <div class="col-12"><button class="btn btn-outline-primary" type="submit">Enregistrer le profil</button></div>
                    </form>
                </div>
            </section>
        </div>
    </div>
    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-end mb-3"><div><p class="eyebrow mb-1">Abonnements</p><h2 class="h4 fw-bold mb-0">Mes formules</h2></div></div>
        <div class="row g-4">
            <?php foreach ($formulas as $formula): ?>
                <div class="col-md-4">
                    <article class="card border-0 shadow-sm h-100 formula-card">
                        <div class="card-body p-4 d-flex flex-column">
                            <h3 class="h5 fw-bold"><?= e($formula['nom']) ?></h3>
                            <p class="display-6 fw-bold mb-0"><?= e(money($formula['prix_base'])) ?></p>
                            <p class="small text-secondary">+ <?= e(money($formula['tarif_par_tranche'])) ?> / tranche de 30 min après <?= e((string) $formula['duree_gratuite_minutes']) ?> min.</p>
                            <ul class="small text-secondary ps-3 mb-4">
                                <li><?= e((string) $formula['duree_gratuite_minutes']) ?> min incluses</li>
                                <li>Supplément électrique : <?= e(money($formula['supplement_electrique'])) ?></li>
                            </ul>
                            <form method="post" action="index.php" class="mt-auto">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="buy_subscription">
                                <input type="hidden" name="id_formule" value="<?= e((string) $formula['id_formule']) ?>">
                                <button class="btn btn-outline-primary w-100" type="submit">Choisir cette formule</button>
                            </form>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h5 fw-bold mb-0">Opérations portefeuille</h2></div>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Date</th><th>Nature</th><th class="text-end">Montant</th></tr></thead><tbody>
                    <?php foreach ($operations as $operation): ?>
                        <tr><td><?= e(frenchDate($operation['date_heure'])) ?></td><td><?= e(statusLabel($operation['nature'])) ?><?= $operation['numero_serie'] ? ' · ' . e($operation['numero_serie']) : '' ?></td><td class="text-end <?= (float) $operation['montant'] < 0 ? 'text-danger' : 'text-success' ?>"><?= e(money($operation['montant'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-0">
                    <div class="p-4 border-bottom"><p class="eyebrow mb-1">Historique</p><h2 class="h5 fw-bold mb-0">Abonnements souscrits</h2></div>
                    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Formule</th><th>Période</th><th>État</th></tr></thead><tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <?php $active = date('Y-m-d') >= $subscription['date_debut'] && date('Y-m-d') <= $subscription['date_fin']; ?>
                        <tr><td><?= e($subscription['formule_nom']) ?></td><td><?= e(frenchDate($subscription['date_debut'], false)) ?> → <?= e(frenchDate($subscription['date_fin'], false)) ?></td><td><?= statusBadge($active ? 'active' : 'expiree') ?></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                </div>
            </section>
        </div>
    </div>
<?php
    renderFooter();
}
