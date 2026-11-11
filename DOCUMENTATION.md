# Documentation technique — Vélo Libre-Service

## 1. Architecture retenue

L’application utilise une architecture web légère, adaptée à un projet de base de données et rapide à déployer sur les ordinateurs du groupe.

```text
Navigateur
    ↓  HTTP
Interface PHP 8 + Bootstrap
    ↓  PDO, requêtes préparées
MySQL / MariaDB
    ├── tables normalisées
    ├── contraintes et index
    ├── triggers et historique
    ├── fonction de tarification
    ├── vues de reporting
    └── événement d’expiration
```

Choix : PHP 8, PDO et Bootstrap 5. PHP est déjà inclus dans XAMPP, PDO fournit une connexion MySQL sûre, et Bootstrap permet une interface responsive sans installer Node.js, React ou un framework lourd.

## 2. Correspondance avec le sujet

| Attendu | Réponse du projet |
| --- | --- |
| Stations, places libres, vélos par type | Vue `v_etat_station` et page Stations en direct |
| Emprunt / restitution | Formulaires usager et triggers `trg_trajet_*` |
| Un seul vélo par usager | Contrôle dans `trg_trajet_bi_controler_emprunt` |
| Abonnement valable | Contrôle du périmètre de dates dans le trigger d’emprunt |
| Vélo défectueux non empruntable | États contrôlés par le trigger d’emprunt |
| Calcul et paiement | Fonction `fn_calculer_cout_trajet`, déclenchée à la restitution |
| Historique des états et positions | Tables `historique_etat`, `historique_position` et triggers vélo |
| Réservation 15 min | Table `reservation`, triggers et événement `ev_expirer_reservations` |
| Maintenance / signalement | Tables `signalement`, `intervention`, écran Opérations |
| Rééquilibrage | Tables `tournee_regulation`, `mouvement_regulation`, écran Régulation |
| Statistiques | Vues `v_statistiques_journalieres`, `v_recettes_par_formule`, écran Statistiques |
| Interface graphique | Application responsive pour usagers et opérateurs |

## 3. Normalisation et intégrité

Le modèle est en troisième forme normale :

- les référentiels (`formule`, `categorie_velo`) sont séparés ;
- les abonnements sont liés aux usagers et aux formules ;
- les trajets portent les clés étrangères vers l’usager, le vélo, les stations et les bornes ;
- les opérations de portefeuille évitent de déduire un solde à partir d’un texte ou d’un calcul non tracé ;
- les signalements sont liés simultanément à un usager, un vélo et une station ;
- les interventions sont liées à un signalement et à un opérateur ;
- l’historisation est séparée des tables opérationnelles.

Les règles structurelles sont protégées par des clés primaires, clés étrangères, contraintes `CHECK`, contraintes `UNIQUE` et index.

## 4. Automatisations MySQL

### Triggers

- un vélo ajouté ou modifié alimente les historiques d’état et de position ;
- une réservation active marque le vélo comme `reserve` ; une annulation ou expiration le libère ;
- un emprunt contrôle l’abonnement, le vélo, la borne et les trajets déjà ouverts ;
- une restitution contrôle la borne, calcule durée et coût, vérifie le solde, déplace le vélo et crée l’opération de paiement si le coût est supérieur à zéro.

### Fonction métier

`fn_calculer_cout_trajet` utilise la formule active de l’usager, le type de vélo, les minutes incluses, les tranches de 30 minutes et le supplément électrique.

Le calcul protège le cas d’un trajet inclus : la différence de durée est convertie en entier signé avant application de `GREATEST`, évitant toute soustraction non signée négative sous MySQL/MariaDB.

### Événement

`ev_expirer_reservations` s’exécute chaque minute et fait passer les réservations de plus de 15 minutes au statut `expiree`.

L’administrateur MySQL doit activer une fois le planificateur :

```sql
SET GLOBAL event_scheduler = ON;
```

## 5. Performance

Les index répondent aux requêtes les plus fréquentes :

| Index | Utilité |
| --- | --- |
| `idx_velo_etat_categorie` | Recherche des vélos disponibles par type |
| `idx_borne_station_etat` | Comptage des bornes disponibles d’une station |
| `idx_trajet_usager_date` | Historique d’un usager |
| `idx_trajet_velo_ouvert` | Vérification rapide d’un vélo déjà emprunté |
| `idx_abonnement_usager_periode` | Validation d’un abonnement à une date donnée |
| `idx_reservation_expiration` | Expiration automatique des réservations |
| `idx_historique_*_velo_date` | Consultation de l’historique d’un vélo |

Les vues évitent de recopier des données agrégées et centralisent les requêtes de reporting. Pour l’oral, on peut montrer un `EXPLAIN` sur l’historique d’un usager ou les réservations à expirer.

## 6. Sécurité applicative

- mots de passe hashés avec bcrypt via `password_hash()` ;
- vérification avec `password_verify()` ;
- requêtes préparées PDO contre les injections SQL ;
- jeton CSRF sur chaque formulaire POST ;
- régénération d’identifiant de session après connexion ;
- contrôle des rôles côté serveur : usager, maintenance, régulation, administration ;
- échappement HTML systématique des données affichées ;
- fichier local de configuration MySQL ignoré par Git.

## 7. Cas de démonstration conseillé

1. Se connecter avec Alice.
2. Ouvrir Stations et montrer les disponibilités temps réel issues de `v_etat_station`.
3. Réserver ou emprunter un vélo.
4. Restituer dans une borne libre : montrer la durée, le coût et le portefeuille.
5. Se connecter avec Sarah (administration).
6. Montrer un signalement, une intervention, la flotte et les statistiques.

Le déroulé détaillé est disponible dans [PRESENTATION_10_MIN.md](PRESENTATION_10_MIN.md).
