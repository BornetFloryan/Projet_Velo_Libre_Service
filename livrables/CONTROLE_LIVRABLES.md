# Contrôle des livrables demandés

## Livrables disponibles

- Présentation : `livrables/Presentation_Velo_Libre_Service.pptx`
- Diagramme EA corrigé SVG : dossier de documentation externe
- Diagramme EA corrigé XML : dossier de documentation externe
- Diagramme use case corrigé SVG : dossier de documentation externe
- Diagramme use case corrigé XML : dossier de documentation externe
- Support oral : `SOUTENANCE_10_MIN.md`
- Schéma SQL : `velo_libre_service_mysql.sql`
- Jeu de données : `donnees_test.sql`
- Benchmark : `benchmark_optimisation.sql`
- Charge légère : `donnees_charge_optimisation.sql`
- Audit 3NF : `livrables/NORMALISATION_3NF.md`
- Cartographie EA/SQL : `livrables/CARTOGRAPHIE_EA_SQL.md`

## Attendus du projet

| Attendu | État | Preuve |
|---|---|---|
| Use case des acteurs | Présent | Diagramme use case corrigé |
| EA Chen corrigé | Présent | Diagramme EA corrigé |
| Schéma relationnel 3NF | Présent | Tables SQL + audit 3NF |
| Contraintes | Présent | Clés primaires, étrangères, UNIQUE, CHECK |
| Index | Présent | 18 index explicites plus index de clés étrangères |
| Trigger | Présent | 10 triggers |
| Procédure stockée | Présent | `sp_expirer_reservations` |
| Fonction métier | Présent | `fn_calculer_cout_trajet` |
| Événements | Présent | Expiration et 6 événements de simulation |
| Historisation | Présent | `historique_position` et `historique_etat` |
| Vues | Présent | 3 vues de reporting |
| Optimisation mesurable | Présent | Benchmark avant/après index composé |
| Jeu de données léger | Présent | 3000 lignes synthétiques puis charge 150000 |
| Application web | Présent | Vue 3 + API Express + MySQL |
| Partitionnement | Écarté | Non justifié à ce volume |

## Corrections nécessaires au livrable EA

Une présentation doit être cohérente avec SQL :

- Ajouter l’entité `Operation portefeuille` dans le diagramme.
- Ajouter les attributs `statut` de la réservation et `date/heure` de réservation.
- Ajouter les attributs de paiement du trajet.
- Retirer le lien direct `Intervention -> Vélo` ou expliquer qu’il est indirect par `Signalement -> Vélo`.
- Conserver `Mouvement régulation` comme relation-objet avec date, nature, tournée, vélo et station.

Ces écarts sont maintenant identifiés dans `CARTOGRAPHIE_EA_SQL.md`.
