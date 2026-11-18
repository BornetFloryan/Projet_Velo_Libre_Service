# Soutenance Vélo libre-service — 10 minutes

Équipe : Floryan Bornet, Tim Carrara, Enzo Vidy

## Déroulé

| Temps | Partie | À montrer |
| --- | --- | --- |
| 0:00–4:00 | Use cases et EA Chen | Diagrammes corrigés, acteurs, principales entités et règles métier |
| 4:00–5:00 | Requête et optimisation | Requête d'historique, `EXPLAIN FORMAT=JSON`, index composé, partitionnement écarté |
| 5:00–9:00 | Application | Réservation usager, puis traitement d'un signalement par maintenance |
| 9:00–10:00 | Automatisation SQL | Trigger de restitution, fonction de coût, procédure et événement d'expiration |

## 0:00–4:00 — Modèle et architecture

### Use cases

Présenter les acteurs : l'usager s'inscrit, consulte les stations, réserve ou emprunte un vélo, restitue le vélo, paie le trajet et consulte son historique. Les opérateurs partagent la connexion, puis les responsabilités diffèrent : maintenance, régulation et administration.

Insister sur la frontière du système et sur le fait que les actions représentées correspondent aux fonctions effectivement démontrées dans l'application.

### EA Chen et conception

Présenter le noyau : `USAGER`, `VELO`, `STATION`, `BORNE`, `RESERVATION`, `TRAJET`. Les tables `HISTORIQUE_POSITION`, `HISTORIQUE_ETAT`, `SIGNALEMENT`, `INTERVENTION`, `TOURNEE_REGULATION`, `MOUVEMENT_REGULATION` et `OPERATION_PORTEFEUILLE` conservent l'activité.

Expliquer une règle de normalisation : les stations ne sont pas répétées dans chaque vélo. Le vélo référence une borne, qui référence une station. Les historiques sont séparés afin de conserver les changements successifs sans écraser l'état courant.

## 4:00–5:00 — Requête complexe et optimisation

Requête à expliquer : afficher la chronologie des déplacements d'un vélo, avec les noms des stations d'origine et de destination, en commençant par le plus récent.

```sql
SELECT h.date_heure, h.type_deplacement,
       station_depart.nom AS ancienne_station,
       station_arrivee.nom AS nouvelle_station
FROM historique_position h
LEFT JOIN borne b_depart ON b_depart.id_borne = h.ancienne_borne
LEFT JOIN station station_depart ON station_depart.id_station = b_depart.id_station
LEFT JOIN borne b_arrivee ON b_arrivee.id_borne = h.nouvelle_borne
LEFT JOIN station station_arrivee ON station_arrivee.id_station = b_arrivee.id_station
WHERE h.numero_serie = 'VELO-001'
ORDER BY h.date_heure DESC;
```

L'index composé est `idx_historique_position_velo_date (numero_serie, date_heure)`. La première colonne filtre le vélo, la seconde trie les résultats par date.

Démonstration : charger le mode « Charge sans index », puis lancer `benchmark_optimisation.sql`. Charger ensuite le mode « Charge optimisée » ou relancer le script jusqu'à obtenir les deux lignes de résultat sur la même base. Présenter uniquement les temps réels affichés par `résultat_benchmark`.

La charge de comparaison ajoute 150 000 positions pour le vrai vélo `VELO-001`, un volume suffisant pour rendre l'effet d'un index composé observable sans rendre le chargement excessif. Les résultats doivent être lus directement dans `résultat_benchmark` lors de la soutenance, car les durées varient selon la machine.

```sql
EXPLAIN FORMAT=JSON
SELECT date_heure, type_deplacement
FROM historique_position
WHERE numero_serie = 'VELO-001'
ORDER BY date_heure DESC;
```

### Partitionnement

Ne pas prétendre que la base actuelle est partitionnée. Le volume actuel ne justifie pas cette technique. Sur MySQL, la création de partitions peut être incompatible avec certaines clés étrangères et rend le maintien du modèle plus complexe. Le partitionnement par date peut être envisagé plus tard si les historiques deviennent très volumineux, avec une politique de purge et une adaptation des contraintes.

## 5:00–9:00 — Démonstration de l'application

### Usager — 2 minutes

1. Se connecter avec `alice.martin@example.test` / `password`.
2. Ouvrir Stations et choisir un vélo disponible.
3. Réserver le vélo pendant 15 minutes.
4. Montrer la réservation dans le tableau de bord ou Mes trajets.

### Maintenance — 2 minutes

1. Se déconnecter et se connecter avec `camille.leroy@example.test` / `password`.
2. Ouvrir Opérations puis Signalements.
3. Montrer le signalement et démarrer ou clôturer une intervention.
4. Ouvrir Flotte et Historique pour montrer l'état et les changements du vélo.

## 9:00–10:00 — Trigger, fonction, procédure et événement

- À la restitution, le trigger vérifie la borne, calcule la durée et le coût, marque le paiement puis met à jour le portefeuille et la position du vélo.
- Les deux triggers d'historisation gardent les changements d'état et de borne.
- `fn_calculer_cout_trajet` centralise le tarif, la durée gratuite et le supplément électrique.
- `sp_expirer_reservations` centralise l'expiration des réservations et la remise en service des vélos.
- `ev_expirer_reservations` appelle cette procédure chaque minute.

## Réponses courtes aux questions

**Pourquoi cet index ?** La page d'historique filtre par numéro de série et trie par date.

**Pourquoi pas plus d'index ?** Un index accélère certaines lectures, mais prend de la place et ralentit les écritures. Chaque index correspond à une requête réelle.

**Pourquoi pas de partitionnement ?** Le volume ne le justifie pas et les clés étrangères du modèle restent prioritaires.

**Les performances sont-elles garanties ?** Non. Elles dépendent du serveur, du cache et de MySQL. Elles sont mesurées sur la même base et avec les mêmes données.

**Que se passe-t-il si le vélo n'est pas rendu ?** Le trajet reste ouvert et le vélo reste en cours. Le coût est calculé à la restitution.

**Comment remettre la démonstration à zéro ?** Le compte administration propose « Réinitialiser la BDD » ou les modes « Charge sans index » et « Charge optimisée ».
