# Vélo Libre-Service — Projet BD70

Application de gestion d’un réseau de vélos en libre-service. Le dépôt contient la base MySQL, un jeu de données réaliste et une interface web de démonstration.

## Fonctionnalités

- connexion et inscription d’un usager ;
- consultation en direct des stations, vélos disponibles et bornes libres ;
- réservation limitée à 15 minutes, emprunt et restitution ;
- calcul automatique du coût, débit du portefeuille et historique des trajets ;
- abonnements, portefeuille et évaluation d’un trajet ;
- signalement d’un problème sur un vélo ;
- espace opérateur : maintenance, flotte, régulation, administration et statistiques ;
- historisation, triggers, fonction métier, procédure stockée, vues de reporting et événements MySQL.

## Démarrage rapide

Prérequis : Node.js 18 ou supérieur et MySQL (XAMPP convient pour MySQL).

1. Démarrer MySQL dans XAMPP.
2. Importer, dans cet ordre, `velo_libre_service_mysql.sql` puis `donnees_test.sql` dans votre outil MySQL.
3. Activer l’évènement d’expiration des réservations :

```sql
SET GLOBAL event_scheduler = ON;
```

`donnees_test.sql` contient un volume de démonstration léger : 3 000 réservations terminées,
3 000 positions et 3 000 changements d'état. Il suffit pour analyser les plans d'exécution
avec `EXPLAIN`, sans perturber les parcours visibles dans l'application.

Le compte administration peut aussi charger, depuis l'application, une base de comparaison sans
index composé ou une base optimisée. Les deux ajoutent le même jeu de charge de 150 000
positions pour `VELO-001`. Le script `benchmark_optimisation.sql` mesure les deux scénarios
et restaure l'index composé à la fin.

4. Copier `.env.example` vers `.env` si les paramètres MySQL par défaut ne conviennent pas.
5. Installer les dépendances puis lancer l’application :

```powershell
npm install
npm start
```

6. Ouvrir <http://localhost:8080> ou double-cliquer sur `lancer_interface.bat`.

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
| --- | --- | --- |
| Usager | `alice.martin@example.test` | `password` |
| Maintenance | `camille.leroy@example.test` | `password` |
| Régulation | `nolan.robert@example.test` | `password` |
| Administration | `sarah.thomas@example.test` | `password` |

Les mots de passe ne sont présents que pour le jeu de démonstration. Ils sont stockés avec bcrypt dans la base.

## Structure du dépôt

```text
.
├── velo_libre_service_mysql.sql   # Schéma, contraintes, vues, triggers, fonction, événements
├── donnees_test.sql               # Jeu de données de démonstration
├── donnees_charge_optimisation.sql# Charge légère de comparaison
├── benchmark_optimisation.sql     # Mesure avant/après index composé
├── server/src/                    # API Express, routes, accès MySQL et sécurité
├── client/                        # SPA Vue 3 et composants de l’interface
├── package.json                   # Dépendances et scripts Node.js
├── tests/                         # Scénarios de validation
├── livrables/                     # Support PowerPoint pour l’oral
├── SOUTENANCE_10_MIN.md           # Déroulé du support oral
└── lancer_interface.bat           # Lancement simple sous Windows
```

## Vérifications

Les scénarios réalisés et leurs résultats attendus sont décrits dans [tests/TESTS.md](tests/TESTS.md). Ils couvrent les règles métier SQL ainsi que les parcours complets de l’interface.

## Volumes de données

Le dépôt contient un volume standard léger et un volume de comparaison séparé :

- 3 000 réservations terminées, positions et changements d'état pour l'environnement normal.
- 150 000 positions supplémentaires pour `VELO-001` uniquement dans les modes de comparaison.

Le volume de comparaison permet de mesurer l'impact d'un index composé sans rendre le chargement trop long. Le partitionnement n'est pas activé dans le schéma : il n'est pas justifié par ce volume et les clés étrangères MySQL rendent son usage plus complexe.
