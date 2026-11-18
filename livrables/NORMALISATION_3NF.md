# Conformité à la 3NF — Vélo Libre-Service

## Conclusion

Le schéma est conforme à la 3NF pour les tables relationnelles principales. Les attributs sont atomiques, chaque table possède une clé primaire, les dépendances fonctionnelles sont rattachées à cette clé et les dépendances transitives entre attributs non clés ont été supprimées par les clés étrangères.

## Points vérifiés

| Table | Dépendances principales | Conforme |
|---|---|---|
| categorie_velo | id_categorie -> libelle | Oui |
| formule | id_formule -> tarif, franchise, supplément | Oui |
| station | id_station -> nom, adresse, GPS, capacité, état, seuils | Oui |
| borne | id_borne -> état, id_station | Oui |
| usager | id_usager -> nom, prénom, email, mot de passe, solde, tarif réduit | Oui |
| abonnement | id_abonnement -> usager, formule, dates | Oui |
| velo | numero_serie -> état, batterie, date, catégorie, borne | Oui |
| reservation | id_reservation -> date, statut, usager, vélo | Oui |
| trajet | id_trajet -> dates, état paiement, usager, vélo, stations, bornes | Oui, avec snapshot tarifaire |
| operation_portefeuille | id_operation -> date, nature, montant, usager, trajet | Oui |
| signalement | id_signalement -> date, description, usager, vélo, station | Oui |
| operateur | id_operateur -> identité, email, rôle | Oui |
| intervention | id_intervention -> dates, type, signalement, opérateur | Oui |
| tournee_regulation | id_tournee -> date, véhicule, opérateur | Oui |
| mouvement_regulation | id_mouvement -> date, nature, tournée, vélo, station | Oui |
| historique_position | id_historique_position -> date, bornes, type, vélo | Oui |
| historique_etat | id_historique_etat -> date, anciens/nouveaux états, motif, vélo | Oui |

## Attributs historiques ou calculés

- `trajet.duree_minutes` et `trajet.cout_facture` sont des instantanés de fin de trajet.
- Ils doivent être conservés même si la formule tarifaire évolue ultérieurement.
- `trajet.statut_paiement` et `trajet.date_paiement` représentent l’état de l’opération de paiement.
- `operation_portefeuille` constitue le registre comptable des mouvements.
- `usager.solde_portefeuille` est une donnée métier courante mise à jour avec le registre. Cette répétition est une optimisation contrôlée afin d’afficher le solde rapidement.

Cette dernière valeur peut être recalculée à partir des opérations. En sens strict, elle constitue une donnée dérivée conservée pour les performances. La version de démonstration doit conserver la cohérence par trigger : chaque paiement ou rechargement doit mettre à jour le solde, et aucune modification directe incohérente ne doit être autorisée.

## Choix qui évitent les anomalies

- La station n’est pas dupliquée dans chaque vélo : le vélo référence une borne, elle-même liée à une station.
- La catégorie du vélo est dans `categorie_velo`, jamais dupliquée dans `velo` comme texte.
- La formule tarifaire est dans `formule` et liée par `abonnement`, pas répétée dans chaque trajet.
- Les états successifs ne remplacent pas les historiques : `historique_position` et `historique_etat` conservent les événements.
- Les opérations financières sont séparées du solde courant : `operation_portefeuille` garde l’historique comptable.

## Synthèse pour l’oral

Le schéma respecte les relations atomiques de la 1NF, une dépendance de chaque attribut non clé à la clé entière de la 2NF, et aucune dépendance entre attributs non clés de la 3NF. Les données calculées sont conservées uniquement lorsqu’elles représentent un résultat historique ou un solde opérationnel contrôlé par les triggers.
