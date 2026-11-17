# Soutenance Vélo libre-service — 10 minutes

Équipe : Floryan Bornet, Tim Carrara, Enzo Vidy  
Créneau communiqué : 15 h 40

## Déroulé

| Temps | Partie | À montrer |
| --- | --- | --- |
| 0:00–4:00 | Use cases et EA Chen | Diagrammes corrigés, acteurs, principales entités et règles métier |
| 4:00–5:00 | Requête et optimisation | Requête d'historique, `EXPLAIN ANALYZE`, index composé, partitionnement écarté |
| 5:00–9:00 | Application | Réservation usager, puis traitement d'un signalement par maintenance |
| 9:00–10:00 | Automatisation SQL | Trigger de restitution, fonction de coût et événement d'expiration |
| 10:00+ | Questions | Chaque membre doit pouvoir défendre les choix et limites |

## 0:00–4:00 — Modèle et architecture

### 0:00–2:00 — Use cases

Présenter les acteurs sans détailler toutes les ovales : l'usager s'inscrit et se connecte, consulte les stations, réserve ou emprunte un vélo, restitue le vélo, paie le trajet et consulte son historique. Les opérateurs partagent la connexion, puis leurs responsabilités diffèrent : maintenance traite les signalements, régulation enregistre les tournées et déplacements, administration gère le réseau et les formules.

Insister sur la frontière du système et sur le fait que les actions représentées correspondent aux fonctions effectivement démontrées dans l'application.

### 2:00–4:00 — EA Chen et conception

Présenter le noyau : `USAGER`, `VELO`, `STATION`, `BORNE`, `RESERVATION`, `TRAJET`. Puis montrer les tables qui gardent l'activité : `HISTORIQUE_POSITION`, `HISTORIQUE_ETAT`, `SIGNALEMENT`, `INTERVENTION`, `TOURNEE_REGULATION`, `MOUVEMENT_REGULATION` et `OPERATION_PORTEFEUILLE`.

Expliquer une règle de normalisation concrète : les informations de station ne sont pas répétées dans chaque vélo, le vélo référence une borne, qui référence la station. Les historiques sont des lignes séparées afin de conserver les événements successifs sans écraser l'état courant du vélo.

Architecture logicielle à annoncer avec exactitude : Vue 3 appelle une API Express. Les routes lisent et modifient les données MySQL. Les requêtes de lecture passent par les couches route, contrôleur, service et repository. Si des actions métier restent directement dans les routes, le dire comme limite à corriger, pas comme une architecture déjà réalisée.

## 4:00–5:00 — Requête complexe et optimisation

Requête à expliquer : afficher la chronologie des déplacements d'un vélo, avec les noms des stations d'origine et de destination, en commençant par le plus récent. Elle joint les historiques de position aux bornes puis aux stations, filtre sur le numéro de série et trie sur la date.

Mesure faite sur une base temporaire issue du jeu initial : 5 024 positions. Après une lecture d'échauffement, 11 exécutions de chaque variante donnent une médiane de **1,385 ms avec l'index** et **1,902 ms en ignorant cet index**. Le client Node mesure l'aller-retour vers le serveur local, donc ces temps servent à comparer ces deux variantes sur cette machine. Ils ne prédisent pas les performances d'un autre ordinateur. `EXPLAIN FORMAT=JSON` confirme que le plan normal choisit `idx_historique_position_velo_date` et estime environ 418 lignes pour `SIM-001`.

```sql
SELECT h.date_heure, h.type_deplacement,
       station_depart.nom AS ancienne_station,
       station_arrivee.nom AS nouvelle_station
FROM historique_position h
LEFT JOIN borne b_depart ON b_depart.id_borne = h.ancienne_borne
LEFT JOIN station station_depart ON station_depart.id_station = b_depart.id_station
LEFT JOIN borne b_arrivee ON b_arrivee.id_borne = h.nouvelle_borne
LEFT JOIN station station_arrivee ON station_arrivee.id_station = b_arrivee.id_station
WHERE h.numero_serie = 'SIM-001'
ORDER BY h.date_heure DESC;
```

L'index composé correspondant est `idx_historique_position_velo_date (numero_serie, date_heure)`. La première colonne sert à retrouver les lignes du vélo, la seconde à les lire dans l'ordre chronologique demandé. Afficher les résultats des deux variantes ci-dessous avec les chiffres observés sur l'ordinateur de démonstration. Ne pas annoncer un gain théorique comme une mesure.

```sql
EXPLAIN ANALYZE
SELECT date_heure, type_deplacement
FROM historique_position
WHERE numero_serie = 'SIM-001'
ORDER BY date_heure DESC;

EXPLAIN ANALYZE
SELECT date_heure, type_deplacement
FROM historique_position IGNORE INDEX (idx_historique_position_velo_date)
WHERE numero_serie = 'SIM-001'
ORDER BY date_heure DESC;
```

Mesurer plusieurs fois et comparer les temps observés, le nombre de lignes examinées et le plan choisi. La variante `IGNORE INDEX` est réservée à la comparaison. L'index occupe de l'espace et doit être mis à jour à chaque écriture.

### Partitionnement

Ne pas prétendre que la base actuelle est partitionnée. Le volume de démonstration est de quelques milliers de lignes par table, trop faible pour justifier le coût opérationnel d'un partitionnement. En outre, MySQL impose des contraintes importantes aux clés étrangères des tables partitionnées. Les index et le filtrage par vélo répondent au besoin mesuré ici. Le partitionnement par date peut être étudié si les historiques atteignent des volumes beaucoup plus élevés et si l'architecture des clés est adaptée.

## 5:00–9:00 — Démonstration de l'application

Préparer les comptes avant de commencer, éviter de saisir les mots de passe en direct si le chronomètre est serré.

### Usager — environ 2 minutes

1. Se connecter avec `alice.martin@example.test` / `password`.
2. Ouvrir Stations et choisir un vélo disponible.
3. Cliquer sur « Réserver 15 min ».
4. Montrer la réservation sur le tableau de bord ou « Mes trajets ».
5. Expliquer que l'événement SQL expire une réservation active après 15 minutes. Ne pas attendre son expiration pendant la démonstration.

### Maintenance — environ 2 minutes

1. Se déconnecter et se connecter avec `camille.leroy@example.test` / `password`.
2. Ouvrir Opérations puis Signalements.
3. Montrer le signalement de démonstration et démarrer ou clôturer l'intervention selon son état.
4. Ouvrir Flotte et montrer l'état du vélo. L'historique des changements est consultable dans Historique.

Alternative si l'équipe préfère montrer la régulation : `nolan.robert@example.test` / `password`, puis créer une tournée et enregistrer un retrait ou dépôt dans l'onglet Régulation.

## 9:00–10:00 — Trigger, fonction et événement

- À la restitution, le trigger vérifie la borne, calcule la durée et le coût, marque le paiement puis met à jour le portefeuille et la position du vélo.
- Les triggers d'historisation ajoutent une ligne quand la position ou l'état d'un vélo change.
- `fn_calculer_cout_trajet` centralise le calcul tarifaire utilisé par la restitution.
- `ev_expirer_reservations` s'exécute chaque minute et libère les vélos dont la réservation active a expiré. Le planificateur MySQL doit être activé.

## Réponses courtes aux questions probables

**Pourquoi cet index ?** La page d'historique filtre par numéro de série et trie par date. Les colonnes de l'index suivent cet ordre d'usage.

**Pourquoi ne pas indexer toutes les colonnes ?** Un index accélère certaines lectures, mais prend de la place et ralentit les écritures. Chaque index doit correspondre à une requête identifiée.

**Pourquoi pas de partitionnement ?** Le volume présent ne le justifie pas. Le partitionnement complexifierait la gestion et les clés étrangères ; il faudrait le réévaluer sur des volumes bien plus grands avec un besoin de purge ou de lecture par période.

**Les performances sont-elles garanties ?** Non. Les durées dépendent de la machine, du cache et de MySQL. On compare le même jeu de données et le même serveur, et on présente les mesures réellement observées.

**Que se passe-t-il sans retour du vélo ?** Le trajet reste ouvert et le vélo reste indisponible. Le coût final est déterminé à la restitution. Le tableau de bord signale les trajets ouverts depuis plus de 24 heures.

**Comment remettre la démonstration à zéro ?** Le compte administration propose « Réinitialiser la BDD ». Cela recharge le schéma et les données initiales.
