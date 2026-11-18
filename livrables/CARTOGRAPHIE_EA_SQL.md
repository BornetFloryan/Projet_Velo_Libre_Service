# Cartographie EA / use case / SQL

## Entités Chen et tables SQL

| Élément EA | Table SQL | Clé logique | Rôle |
|---|---|---|---|
| Formule | formule | id_formule | Tarifs |
| Usager | usager | id_usager | Comptes usagers |
| Abonnement | abonnement | id_abonnement | Relation usager-formule dans le temps |
| Trajet | trajet | id_trajet | Emprunt, restitution, paiement |
| Vélo | velo | numero_serie | État, batterie, catégorie, position |
| Catégorie vélo | categorie_velo | id_categorie | Mécanique ou électrique |
| Station | station | id_station | Réseau de stations |
| Borne | borne | id_borne | Emplacement physique |
| Réservation | reservation | id_reservation | Réserve un vélo pour un usager |
| Signalement | signalement | id_signalement | Problème déclaré |
| Intervention maintenance | intervention | id_intervention | Traitement d’un signalement |
| Opérateur | operateur | id_operateur | Maintenance, régulation, administration |
| Tournée régulation | tournee_regulation | id_tournee | Tournée réalisée |
| Mouvement régulation | mouvement_regulation | id_mouvement | Retrait ou dépôt |
| Historique position | historique_position | id_historique_position | Historique des positions |
| Historique état | historique_etat | id_historique_etat | Historique des états |

## Relations présentes mais absentes du diagramme initial

Les relations suivantes sont présentes dans la base et doivent apparaître dans une version corrigée du diagramme :

- `usager` possède des `operation_portefeuille` : 1 vers 0..*.
- `trajet` peut produire une `operation_portefeuille` : 0..1 vers 1.
- `usager` réalise plusieurs `reservation` : 1 vers 0..*.
- `velo` reçoit plusieurs `reservation` : 1 vers 0..*.
- `reservation` appartient à un usager et à un vélo : dans SQL, ces liens sont portés par les clés étrangères.

## Relations à corriger dans le diagramme Chen

- La relation « Répare » reliant directement `Intervention` à `Vélo` est redondante : l’intervention concerne un `Signalement`, qui concerne déjà un vélo. Le chemin correct est `Intervention -> Signalement -> Vélo`.
- `Mouvement régulation` doit garder ses attributs `date_heure` et `nature` et relier `Tournée`, `Vélo` et `Station`.
- La réservation doit apparaître avec ses attributs `date_reservation` et `statut`.
- Les paiements doivent être visibles : `Trajet.statut_paiement`, `Trajet.date_paiement` et `Operation portefeuille`.

## Cardinalités principales

- Une formule peut appartenir à plusieurs abonnements ; un abonnement concerne une formule et un usager.
- Une station possède 1..* bornes.
- Une borne accueille 0..1 vélo.
- Un vélo appartient à une catégorie.
- Un trajet est réalisé par un usager et mobilise un vélo.
- Un trajet possède un point de départ obligatoire et un point d’arrivée optionnel jusqu’à restitution.
- Un signalement concerne un vélo, une station et un usager déclarant.
- Une intervention concerne un signalement et est réalisée par un opérateur.
- Une tournée est réalisée par un opérateur et contient 1..* mouvements.

## Cohérence entre use case et SQL

Toutes les actions principales du use case sont implémentées : inscription/connexion, consultation des stations, réservation, emprunt, restitution, paiement, historique, signalement, maintenance, régulation, administration et visualisation des statistiques.
