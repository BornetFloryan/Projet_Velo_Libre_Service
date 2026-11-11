# Scénarios de tests

Les contrôles sont réalisés sur une copie temporaire de la base afin de préserver le jeu de démonstration.

## Tests de règles métier MySQL

| Scénario | Résultat attendu |
| --- | --- |
| Emprunter `VELO-005` en maintenance | Refus : vélo indisponible ou défectueux |
| Emprunter un second vélo avec un usager ayant déjà un trajet | Refus : un seul vélo à la fois |
| Donner une station ne correspondant pas à la borne de départ | Refus par le trigger |
| Restituer sur une borne déjà occupée | Refus par le trigger |
| Réserver un vélo indisponible | Refus par le trigger |
| Restituer un trajet de 10 minutes avec abonnement mensuel | Coût 0 €, aucun débit portefeuille |
| Restituer un vélo électrique après 60 minutes avec abonnement réduit | Coût 0,45 €, débit et opération portefeuille |
| Réserver puis annuler un vélo | État `reserve` puis retour à `disponible` |

## Tests de l’interface

| Parcours | Résultat attendu |
| --- | --- |
| Inscription | Un nouvel usager est créé avec hash de mot de passe bcrypt |
| Connexion usager | Tableau de bord, stations, trajets et compte accessibles |
| Réservation | Vélo réservé pendant 15 minutes puis annulation possible |
| Emprunt / restitution | Trajet créé, vélo déplacé, coût et paiement automatisés |
| Signalement | Signalement lié à l’usager, au vélo et à la station |
| Portefeuille | Recharge et opération financière cohérentes |
| Connexion administrateur | Supervision, flotte, régulation, administration et statistiques accessibles |
| Intervention | Création puis clôture possibles pour l’opérateur concerné |
| Régulation | Création de tournée et enregistrement de mouvement possibles |

## État de la validation

La campagne complète ci-dessus a été exécutée avec succès le 11/11/2026 sur une copie temporaire MySQL. Les contrôles incluaient également la vérification visuelle des écrans Connexion, Tableau de bord, Stations et Administration.
