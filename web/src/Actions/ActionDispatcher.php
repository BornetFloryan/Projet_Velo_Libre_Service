<?php

declare(strict_types=1);

function postReturnPage(string $fallback): string
{
    $allowedPages = ['login', 'dashboard', 'stations', 'trajets', 'compte', 'operations', 'statistiques'];
    $requestedPage = (string) ($_POST['return_page'] ?? '');

    return in_array($requestedPage, $allowedPages, true) ? $requestedPage : $fallback;
}

function decimalInput(string $key, float $minimum = 0.0, ?float $maximum = null): float
{
    $rawValue = str_replace(',', '.', trim((string) ($_POST[$key] ?? '')));
    if ($rawValue === '' || !is_numeric($rawValue)) {
        throw new RuntimeException('Un montant ou une valeur numérique est invalide.');
    }

    $value = (float) $rawValue;
    if ($value < $minimum || ($maximum !== null && $value > $maximum)) {
        throw new RuntimeException('Une valeur est en dehors de la plage autorisée.');
    }

    return $value;
}

function handlePost(PDO $pdo): never
{
    $action = (string) ($_POST['action'] ?? '');
    $returnPage = postReturnPage($action === 'login' || $action === 'register' ? 'login' : 'dashboard');

    try {
        verifyCsrf();

        switch ($action) {
            case 'login':
                $email = filter_var(requiredString('email'), FILTER_VALIDATE_EMAIL);
                $password = requiredString('password', 255);
                if ($email === false) {
                    throw new RuntimeException('L’adresse e-mail est invalide.');
                }

                $account = fetchOne(
                    $pdo,
                    'SELECT id_usager AS id, CONCAT(prenom, " ", nom) AS name, email, mot_de_passe_hash
                     FROM usager WHERE email = :email',
                    ['email' => $email]
                );

                if ($account !== null && password_verify($password, $account['mot_de_passe_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['actor'] = [
                        'type' => 'usager',
                        'id' => (int) $account['id'],
                        'name' => $account['name'],
                        'email' => $account['email'],
                    ];
                    flash('success', 'Bienvenue ' . $account['name'] . ' !');
                    redirectTo('dashboard');
                }

                $operator = fetchOne(
                    $pdo,
                    'SELECT id_operateur AS id, nom_prenom AS name, email, mot_de_passe_hash, role_operateur AS role
                     FROM operateur WHERE email = :email',
                    ['email' => $email]
                );

                if ($operator !== null && password_verify($password, $operator['mot_de_passe_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['actor'] = [
                        'type' => 'operateur',
                        'id' => (int) $operator['id'],
                        'name' => $operator['name'],
                        'email' => $operator['email'],
                        'role' => $operator['role'],
                    ];
                    flash('success', 'Connexion opérateur réussie.');
                    redirectTo('dashboard');
                }

                throw new RuntimeException('Adresse e-mail ou mot de passe incorrect.');

            case 'register':
                $prenom = requiredString('prenom', 80);
                $nom = requiredString('nom', 80);
                $email = filter_var(requiredString('email'), FILTER_VALIDATE_EMAIL);
                $password = requiredString('password', 255);
                if ($email === false) {
                    throw new RuntimeException('L’adresse e-mail est invalide.');
                }
                if (mb_strlen($password) < 8) {
                    throw new RuntimeException('Le mot de passe doit contenir au moins 8 caractères.');
                }

                $statement = $pdo->prepare(
                    'INSERT INTO usager (nom, prenom, email, mot_de_passe_hash)
                     VALUES (:nom, :prenom, :email, :passwordHash)'
                );
                $statement->execute([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
                ]);
                flash('success', 'Compte créé. Vous pouvez maintenant vous connecter.');
                redirectTo('login');

            case 'logout':
                $_SESSION = [];
                session_destroy();
                header('Location: ' . appUrl('login'));
                exit;

            case 'reserve_bike':
                $actor = requireUsager();
                $serial = requiredString('numero_serie', 50);
                $statement = $pdo->prepare(
                    'INSERT INTO reservation (id_usager, numero_serie) VALUES (:usager, :serial)'
                );
                $statement->execute(['usager' => $actor['id'], 'serial' => $serial]);
                flash('success', 'Vélo réservé pendant 15 minutes.');
                $returnPage = postReturnPage('stations');
                break;

            case 'cancel_reservation':
                $actor = requireUsager();
                $reservationId = optionalInt($_POST['id_reservation'] ?? null);
                if ($reservationId === null) {
                    throw new RuntimeException('Réservation invalide.');
                }
                $statement = $pdo->prepare(
                    "UPDATE reservation SET statut = 'annulee'
                     WHERE id_reservation = :id AND id_usager = :usager AND statut = 'active'"
                );
                $statement->execute(['id' => $reservationId, 'usager' => $actor['id']]);
                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException('Cette réservation ne peut plus être annulée.');
                }
                flash('success', 'Réservation annulée : le vélo est de nouveau disponible.');
                $returnPage = postReturnPage('trajets');
                break;

            case 'start_trip':
                $actor = requireUsager();
                $serial = requiredString('numero_serie', 50);
                $stationId = optionalInt($_POST['id_station_depart'] ?? null);
                $borneId = optionalInt($_POST['id_borne_depart'] ?? null);
                if ($stationId === null || $borneId === null) {
                    throw new RuntimeException('La station et la borne de départ sont obligatoires.');
                }

                $statement = $pdo->prepare(
                    'INSERT INTO trajet (date_depart, id_usager, numero_serie, id_station_depart, id_borne_depart)
                     VALUES (NOW(), :usager, :serial, :station, :borne)'
                );
                $statement->execute([
                    'usager' => $actor['id'],
                    'serial' => $serial,
                    'station' => $stationId,
                    'borne' => $borneId,
                ]);
                flash('success', 'Trajet démarré. Bonne route !');
                $returnPage = 'trajets';
                break;

            case 'return_trip':
                $actor = requireUsager();
                $tripId = optionalInt($_POST['id_trajet'] ?? null);
                $stationId = optionalInt($_POST['id_station_arrivee'] ?? null);
                $borneId = optionalInt($_POST['id_borne_arrivee'] ?? null);
                if ($tripId === null || $stationId === null || $borneId === null) {
                    throw new RuntimeException('Choisissez une station et une borne de restitution.');
                }

                $statement = $pdo->prepare(
                    'UPDATE trajet
                     SET date_arrivee = NOW(), id_station_arrivee = :station, id_borne_arrivee = :borne
                     WHERE id_trajet = :trip AND id_usager = :usager AND date_arrivee IS NULL'
                );
                $statement->execute([
                    'station' => $stationId,
                    'borne' => $borneId,
                    'trip' => $tripId,
                    'usager' => $actor['id'],
                ]);
                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException('Ce trajet ne peut plus être restitué.');
                }
                flash('success', 'Vélo restitué. Le coût a été calculé automatiquement.');
                $returnPage = 'trajets';
                break;

            case 'rate_trip':
                $actor = requireUsager();
                $tripId = optionalInt($_POST['id_trajet'] ?? null);
                $rating = optionalInt($_POST['note_evaluation'] ?? null);
                if ($tripId === null || $rating === null || $rating < 1 || $rating > 5) {
                    throw new RuntimeException('La note doit être comprise entre 1 et 5.');
                }
                $statement = $pdo->prepare(
                    'UPDATE trajet SET note_evaluation = :rating
                     WHERE id_trajet = :trip AND id_usager = :usager AND date_arrivee IS NOT NULL'
                );
                $statement->execute(['rating' => $rating, 'trip' => $tripId, 'usager' => $actor['id']]);
                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException('Ce trajet ne peut pas être évalué.');
                }
                flash('success', 'Merci pour votre évaluation.');
                $returnPage = 'trajets';
                break;

            case 'recharge_wallet':
                $actor = requireUsager();
                $amount = decimalInput('montant', 1.0, 200.0);
                $pdo->beginTransaction();
                try {
                    $update = $pdo->prepare(
                        'UPDATE usager SET solde_portefeuille = solde_portefeuille + :amount WHERE id_usager = :id'
                    );
                    $update->execute(['amount' => $amount, 'id' => $actor['id']]);
                    $operation = $pdo->prepare(
                        "INSERT INTO operation_portefeuille (nature, montant, id_usager)
                         VALUES ('rechargement', :amount, :id)"
                    );
                    $operation->execute(['amount' => $amount, 'id' => $actor['id']]);
                    $pdo->commit();
                } catch (Throwable $exception) {
                    $pdo->rollBack();
                    throw $exception;
                }
                flash('success', 'Portefeuille rechargé de ' . money($amount) . '.');
                $returnPage = 'compte';
                break;

            case 'buy_subscription':
                $actor = requireUsager();
                $formulaId = optionalInt($_POST['id_formule'] ?? null);
                if ($formulaId === null) {
                    throw new RuntimeException('Formule invalide.');
                }
                $formula = fetchOne($pdo, 'SELECT id_formule, nom FROM formule WHERE id_formule = :id', ['id' => $formulaId]);
                if ($formula === null) {
                    throw new RuntimeException('Cette formule n’existe plus.');
                }

                $latestEndDate = fetchValue(
                    $pdo,
                    'SELECT MAX(date_fin) FROM abonnement WHERE id_usager = :id AND date_fin >= CURDATE()',
                    ['id' => $actor['id']]
                );
                $start = new DateTimeImmutable('today');
                if ($latestEndDate !== null) {
                    $start = (new DateTimeImmutable((string) $latestEndDate))->modify('+1 day');
                }
                $formulaName = mb_strtolower((string) $formula['nom']);
                $duration = str_contains($formulaName, 'annuel') ? 365 : (str_contains($formulaName, 'mensuel') ? 30 : 1);
                $end = $start->modify('+' . ($duration - 1) . ' days');

                $statement = $pdo->prepare(
                    'INSERT INTO abonnement (date_debut, date_fin, id_usager, id_formule)
                     VALUES (:start, :end, :usager, :formula)'
                );
                $statement->execute([
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->format('Y-m-d'),
                    'usager' => $actor['id'],
                    'formula' => $formulaId,
                ]);
                flash('success', 'Abonnement « ' . $formula['nom'] . ' » enregistré.');
                $returnPage = 'compte';
                break;

            case 'update_profile':
                $actor = requireUsager();
                $prenom = requiredString('prenom', 80);
                $nom = requiredString('nom', 80);
                $email = filter_var(requiredString('email'), FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    throw new RuntimeException('L’adresse e-mail est invalide.');
                }
                $statement = $pdo->prepare(
                    'UPDATE usager SET prenom = :prenom, nom = :nom, email = :email WHERE id_usager = :id'
                );
                $statement->execute(['prenom' => $prenom, 'nom' => $nom, 'email' => $email, 'id' => $actor['id']]);
                $_SESSION['actor']['name'] = $prenom . ' ' . $nom;
                $_SESSION['actor']['email'] = $email;
                flash('success', 'Profil mis à jour.');
                $returnPage = 'compte';
                break;

            case 'report_issue':
                $actor = requireUsager();
                $serial = requiredString('numero_serie', 50);
                $stationId = optionalInt($_POST['id_station'] ?? null);
                $description = requiredString('description', 2000);
                if ($stationId === null) {
                    throw new RuntimeException('La station concernée est obligatoire.');
                }
                $bikeAtStation = fetchValue(
                    $pdo,
                    'SELECT COUNT(*) FROM velo v JOIN borne b ON b.id_borne = v.id_borne_actuelle
                     WHERE v.numero_serie = :serial AND b.id_station = :station',
                    ['serial' => $serial, 'station' => $stationId]
                );
                if ((int) $bikeAtStation !== 1) {
                    throw new RuntimeException('Le vélo sélectionné ne se trouve pas dans cette station.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO signalement (description, id_usager, numero_serie, id_station)
                     VALUES (:description, :usager, :serial, :station)'
                );
                $statement->execute([
                    'description' => $description,
                    'usager' => $actor['id'],
                    'serial' => $serial,
                    'station' => $stationId,
                ]);
                flash('success', 'Signalement envoyé à l’équipe de maintenance.');
                $returnPage = postReturnPage('stations');
                break;

            case 'update_bike':
                requireOperator(['maintenance', 'administration']);
                $serial = requiredString('numero_serie', 50);
                $state = requiredString('etat', 30);
                $allowedStates = ['disponible', 'maintenance', 'atelier', 'hors_service'];
                if (!in_array($state, $allowedStates, true)) {
                    throw new RuntimeException('État de vélo invalide.');
                }
                $battery = optionalInt($_POST['niveau_batterie'] ?? null);
                if ($battery !== null && ($battery < 0 || $battery > 100)) {
                    throw new RuntimeException('Le niveau de batterie doit être compris entre 0 et 100.');
                }
                $borneId = optionalInt($_POST['id_borne_actuelle'] ?? null);
                $bike = fetchOne($pdo, 'SELECT id_borne_actuelle FROM velo WHERE numero_serie = :serial', ['serial' => $serial]);
                if ($bike === null) {
                    throw new RuntimeException('Vélo introuvable.');
                }
                if ($state === 'disponible' && $borneId === null && $bike['id_borne_actuelle'] === null) {
                    throw new RuntimeException('Un vélo disponible doit être placé dans une borne.');
                }
                if ($borneId !== null) {
                    $borneFree = fetchValue(
                        $pdo,
                        'SELECT COUNT(*) FROM borne b LEFT JOIN velo v ON v.id_borne_actuelle = b.id_borne
                         WHERE b.id_borne = :borne AND b.etat = "disponible"
                           AND (v.numero_serie IS NULL OR v.numero_serie = :serial)',
                        ['borne' => $borneId, 'serial' => $serial]
                    );
                    if ((int) $borneFree !== 1) {
                        throw new RuntimeException('La borne choisie est indisponible ou déjà occupée.');
                    }
                }
                $statement = $pdo->prepare(
                    'UPDATE velo
                     SET etat = :state, niveau_batterie = :battery,
                         id_borne_actuelle = COALESCE(:borne, id_borne_actuelle)
                     WHERE numero_serie = :serial'
                );
                $statement->execute(['state' => $state, 'battery' => $battery, 'borne' => $borneId, 'serial' => $serial]);
                flash('success', 'État du vélo mis à jour.');
                $returnPage = 'operations';
                break;

            case 'create_intervention':
                $actor = requireOperator(['maintenance', 'administration']);
                $signalId = optionalInt($_POST['id_signalement'] ?? null);
                $typeAction = requiredString('type_action', 120);
                if ($signalId === null) {
                    throw new RuntimeException('Signalement invalide.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO intervention (date_debut, type_action, id_signalement, id_operateur)
                     VALUES (NOW(), :action, :signal, :operator)'
                );
                $statement->execute(['action' => $typeAction, 'signal' => $signalId, 'operator' => $actor['id']]);
                flash('success', 'Intervention ouverte.');
                $returnPage = 'operations';
                break;

            case 'finish_intervention':
                $actor = requireOperator(['maintenance', 'administration']);
                $interventionId = optionalInt($_POST['id_intervention'] ?? null);
                if ($interventionId === null) {
                    throw new RuntimeException('Intervention invalide.');
                }
                $statement = $pdo->prepare(
                    'UPDATE intervention SET date_fin = NOW()
                     WHERE id_intervention = :id AND id_operateur = :operator AND date_fin IS NULL'
                );
                $statement->execute(['id' => $interventionId, 'operator' => $actor['id']]);
                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException('Cette intervention ne peut pas être clôturée.');
                }
                flash('success', 'Intervention clôturée.');
                $returnPage = 'operations';
                break;

            case 'create_tournee':
                $actor = requireOperator(['regulation', 'administration']);
                $date = requiredString('date_tournee', 10);
                $vehicle = requiredString('immatriculation_vehicule', 20);
                $dateObject = DateTimeImmutable::createFromFormat('Y-m-d', $date);
                if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
                    throw new RuntimeException('Date de tournée invalide.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO tournee_regulation (date_tournee, immatriculation_vehicule, id_operateur)
                     VALUES (:date, :vehicle, :operator)'
                );
                $statement->execute(['date' => $date, 'vehicle' => $vehicle, 'operator' => $actor['id']]);
                flash('success', 'Tournée de régulation créée.');
                $returnPage = 'operations';
                break;

            case 'add_movement':
                requireOperator(['regulation', 'administration']);
                $tourneeId = optionalInt($_POST['id_tournee'] ?? null);
                $stationId = optionalInt($_POST['id_station'] ?? null);
                $serial = requiredString('numero_serie', 50);
                $nature = requiredString('nature', 10);
                if ($tourneeId === null || $stationId === null || !in_array($nature, ['retrait', 'depot'], true)) {
                    throw new RuntimeException('Informations de mouvement invalides.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO mouvement_regulation (date_heure, nature, id_tournee, numero_serie, id_station)
                     VALUES (NOW(), :nature, :tournee, :serial, :station)'
                );
                $statement->execute([
                    'nature' => $nature,
                    'tournee' => $tourneeId,
                    'serial' => $serial,
                    'station' => $stationId,
                ]);
                flash('success', 'Mouvement de régulation enregistré.');
                $returnPage = 'operations';
                break;

            case 'update_station':
                requireOperator(['administration']);
                $stationId = optionalInt($_POST['id_station'] ?? null);
                $state = requiredString('etat', 30);
                $minBikes = optionalInt($_POST['seuil_velos_min'] ?? null);
                $minBores = optionalInt($_POST['seuil_bornes_min'] ?? null);
                if ($stationId === null || $minBikes === null || $minBores === null
                    || !in_array($state, ['en_service', 'hors_service', 'en_travaux'], true)) {
                    throw new RuntimeException('Paramètres de station invalides.');
                }
                $capacity = fetchValue($pdo, 'SELECT capacite_bornes FROM station WHERE id_station = :id', ['id' => $stationId]);
                if ($capacity === false || $minBikes < 0 || $minBores < 0 || $minBikes > (int) $capacity || $minBores > (int) $capacity) {
                    throw new RuntimeException('Les seuils doivent être compris entre 0 et la capacité de la station.');
                }
                $statement = $pdo->prepare(
                    'UPDATE station SET etat = :state, seuil_velos_min = :bikes, seuil_bornes_min = :bores
                     WHERE id_station = :station'
                );
                $statement->execute(['state' => $state, 'bikes' => $minBikes, 'bores' => $minBores, 'station' => $stationId]);
                flash('success', 'Station mise à jour.');
                $returnPage = 'operations';
                break;

            case 'create_formula':
                requireOperator(['administration']);
                $name = requiredString('nom', 80);
                $price = decimalInput('prix_base', 0, 9999);
                $freeDuration = optionalInt($_POST['duree_gratuite_minutes'] ?? null);
                $perSlice = decimalInput('tarif_par_tranche', 0, 9999);
                $electricExtra = decimalInput('supplement_electrique', 0, 9999);
                if ($freeDuration === null || $freeDuration < 0 || $freeDuration > 1440) {
                    throw new RuntimeException('Durée incluse invalide.');
                }
                $statement = $pdo->prepare(
                    'INSERT INTO formule (nom, prix_base, duree_gratuite_minutes, tarif_par_tranche, supplement_electrique)
                     VALUES (:name, :price, :duration, :slice, :extra)'
                );
                $statement->execute([
                    'name' => $name,
                    'price' => $price,
                    'duration' => $freeDuration,
                    'slice' => $perSlice,
                    'extra' => $electricExtra,
                ]);
                flash('success', 'Formule tarifaire créée.');
                $returnPage = 'operations';
                break;

            default:
                throw new RuntimeException('Action inconnue.');
        }
    } catch (Throwable $exception) {
        flash('danger', businessMessage($exception));
    }

    redirectTo($returnPage);
}
