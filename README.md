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
- historisation, triggers, fonction métier, vues de reporting et événement MySQL.

## Démarrage rapide

Prérequis : Node.js 18 ou supérieur et MySQL (XAMPP convient pour MySQL).

1. Démarrer MySQL dans XAMPP.
2. Importer, dans cet ordre, `velo_libre_service_mysql.sql` puis `donnees_test.sql` dans votre outil MySQL.
3. Activer l’évènement d’expiration des réservations :

```sql
SET GLOBAL event_scheduler = ON;
```

Les événements de simulation sont volontairement désactivés. Ils n’utilisent que les comptes
`simulation.*` et les vélos `SIM-*` du jeu de données. Pour animer la démonstration :

```sql
ALTER EVENT ev_simuler_reservation_demo ENABLE;
ALTER EVENT ev_simuler_panne_demo ENABLE;
ALTER EVENT ev_simuler_remise_service_demo ENABLE;
```

Pour les arrêter, remplacer `ENABLE` par `DISABLE`. L’expiration des réservations reste un
événement métier réel ; les trois événements `SIM-*` servent uniquement à la démonstration.

Pour comparer les plans d’exécution avec un volume plus important, importer ensuite
`donnees_performance.sql`. Il ajoute 24 000 lignes d’historique de façon réinitialisable et
termine par deux requêtes `EXPLAIN`, avec et sans utilisation de l’index d’historique.

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
├── velo_libre_service_mysql.sql   # Schéma, contraintes, vues, triggers, fonction, événement
├── donnees_test.sql               # Jeu de données de démonstration
├── donnees_performance.sql         # Volume optionnel pour tester les index
├── server/src/                    # API Express, routes, accès MySQL et sécurité
├── client/                        # SPA Vue 3 et composants de l’interface
├── package.json                   # Dépendances et scripts Node.js
├── tests/                         # Scénarios de validation
├── livrables/                     # Support PowerPoint pour l’oral
├── DOCUMENTATION.md               # Architecture et choix défendables à l’oral
├── PRESENTATION_10_MIN.md         # Déroulé du support oral
└── lancer_interface.bat           # Lancement simple sous Windows
```

## Dépôt collaboratif

Le dépôt est partagé sur GitHub : <https://github.com/BornetFloryan/Projet_Velo_Libre_Service>.

Avant de commencer une modification :

```powershell
git pull
```

Après une modification testée :

```powershell
git add .
git commit -m "Description courte de la modification"
git push
```

## Vérifications

Les scénarios réalisés et leurs résultats attendus sont décrits dans [tests/TESTS.md](tests/TESTS.md). Ils couvrent les règles métier SQL ainsi que les parcours complets de l’interface.
