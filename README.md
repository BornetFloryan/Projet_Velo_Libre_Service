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

Prérequis : XAMPP avec MySQL et PHP 8 (ou PHP 8 avec l’extension `pdo_mysql`).

1. Démarrer MySQL dans XAMPP.
2. Importer, dans cet ordre, `velo_libre_service_mysql.sql` puis `donnees_test.sql` dans phpMyAdmin.
3. Activer l’évènement d’expiration des réservations :

```sql
SET GLOBAL event_scheduler = ON;
```

4. Double-cliquer sur `lancer_interface.bat`, ou exécuter la commande suivante depuis le dossier du projet :

```powershell
C:\xampp\php\php.exe -S localhost:8080 -t web
```

5. Ouvrir <http://localhost:8080>.

Si MySQL n’utilise pas l’installation XAMPP par défaut, copier `web/config.example.php` vers `web/config.local.php` puis renseigner les identifiants. Ce dernier fichier n’est pas versionné.

## Comptes de démonstration

| Rôle | E-mail | Mot de passe |
| --- | --- | --- |
| Usager | `alice.martin@example.test` | `password` |
| Maintenance | `camille.leroy@example.test` | `password` |
| Régulation | `nolan.robert@example.test` | `password` |
| Administration | `sarah.thomas@example.test` | `password` |

Les mots de passe ne sont présents que pour le jeu de démonstration. Ils sont stockés avec `password_hash()` / bcrypt dans la base.

## Structure du dépôt

```text
.
├── velo_libre_service_mysql.sql   # Schéma, contraintes, vues, triggers, fonction, événement
├── donnees_test.sql               # Jeu de données de démonstration
├── web/                           # Interface PHP + Bootstrap
├── tests/                         # Scénarios de validation
├── livrables/                     # Support PowerPoint pour l’oral
├── DOCUMENTATION.md               # Architecture et choix défendables à l’oral
├── PRESENTATION_10_MIN.md         # Déroulé du support oral
└── lancer_interface.bat           # Lancement simple sous XAMPP / Windows
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
