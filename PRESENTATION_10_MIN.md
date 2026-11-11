# Support oral — 10 minutes

## Répartition du temps

| Temps | Partie | Message à transmettre |
| --- | --- | --- |
| 0:00–0:30 | Introduction | Problème : gérer un service de vélos partagé et ses contraintes métier. |
| 0:30–1:30 | Architecture | Navigateur → PHP/PDO → MySQL ; solution légère, déployable avec XAMPP. |
| 1:30–2:30 | Conception | Use case, Chen, MLD puis tables normalisées. |
| 2:30–3:45 | Règles métier | Triggers : emprunt, restitution, réservation, historisation. |
| 3:45–5:00 | Qualité | Sécurité, index, vues, événement et tests. |
| 5:00–7:15 | Démo usager | Connexion, station, emprunt, restitution, historique / portefeuille. |
| 7:15–9:15 | Démo opérateur | Signalement, intervention, flotte, régulation, statistiques. |
| 9:15–10:00 | Conclusion | Bilan, limites assumées et ouverture aux questions. |

## Slide 1 — Contexte et objectif

**Titre :** Vélo Libre-Service — BD70

- Gérer stations, vélos, usagers, abonnements et maintenance.
- Fournir une disponibilité en temps réel et conserver l’historique.
- Démontrer la maîtrise de MySQL et de l’intégration applicative.

À dire : « Nous avons construit un système cohérent de bout en bout : conception, base relationnelle, automatisation et interface. »

## Slide 2 — Architecture

Afficher le schéma : `Interface PHP/Bootstrap → PDO → MySQL`.

- PHP + Bootstrap : rapide à déployer sur XAMPP, pas de dépendances lourdes.
- PDO : requêtes préparées et connexion sûre.
- MySQL : lieu des règles critiques, pas seulement stockage passif.

## Slide 3 — De la conception au MLD

- Use case : usager, maintenance, régulation, administration.
- Chen : signalement ternaire usager / vélo / station ; intervention liée au signalement.
- 3NF : les formules, opérations portefeuille, historiques et tournées sont séparés.

À montrer : les diagrammes validés sur diagrams.net.

## Slide 4 — Règles métier automatisées

- Un usager ne peut pas emprunter deux vélos.
- Le vélo doit être disponible, présent sur la bonne borne et non réservé par autrui.
- À la restitution, le coût est calculé et le portefeuille débité.
- Réservation expirée après 15 minutes.
- Les changements d’état et de position sont historisés.

À montrer : une courte capture de `trg_trajet_bi_controler_emprunt` et `fn_calculer_cout_trajet`.

## Slide 5 — Performance et sécurité

- Index ciblés sur disponibilités, trajets ouverts, abonnements et historiques.
- Vues pour l’état des stations et les rapports.
- Mots de passe bcrypt, PDO, CSRF, sessions et rôles.
- Tests SQL et E2E réalisés sur une copie de la base.

## Slide 6 — Démonstration usager

Se connecter : `alice.martin@example.test` / `password`.

1. Ouvrir Stations.
2. Montrer vélos mécaniques, électriques et bornes libres.
3. Emprunter un vélo disponible.
4. Restituer dans une borne libre.
5. Ouvrir Mes trajets puis Mon compte pour montrer coût et portefeuille.

## Slide 7 — Démonstration opérateur

Se connecter : `sarah.thomas@example.test` / `password`.

1. Ouvrir Opérations → Signalements.
2. Créer ou clôturer une intervention.
3. Ouvrir Flotte puis Régulation.
4. Ouvrir Statistiques : recettes par formule et activité journalière.

## Slide 8 — Conclusion et questions possibles

Conclusion : le système respecte les parcours du sujet, protège les données par la base elle-même et fournit une interface démontrable.

Questions probables :

- **Pourquoi des triggers ?** Pour garantir une règle même si une autre interface écrit directement dans la base.
- **Pourquoi une table d’opérations portefeuille ?** Pour conserver une trace auditable des rechargements et paiements.
- **Pourquoi l’événement ?** Une réservation doit expirer même si aucun utilisateur ne se reconnecte.
- **Pourquoi PHP/Bootstrap ?** Le choix réduit l’installation tout en laissant la valeur ajoutée au modèle et aux règles MySQL.
