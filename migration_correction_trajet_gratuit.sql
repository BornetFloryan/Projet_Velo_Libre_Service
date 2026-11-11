-- ============================================================
-- Correctif : restitution d'un trajet gratuit
-- À exécuter une seule fois sur une base déjà créée.
-- ============================================================

DROP FUNCTION IF EXISTS fn_calculer_cout_trajet;

DELIMITER $$

CREATE FUNCTION fn_calculer_cout_trajet (
    p_id_usager INT UNSIGNED,
    p_numero_serie VARCHAR(50),
    p_date_depart DATETIME,
    p_date_arrivee DATETIME
)
RETURNS DECIMAL(10,2)
READS SQL DATA
BEGIN
    DECLARE v_prix_base DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_duree_gratuite SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_tarif_tranche DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_supplement_electrique DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_est_electrique BOOLEAN DEFAULT FALSE;
    DECLARE v_duree_minutes INT DEFAULT 0;
    DECLARE v_formule_trouvee BOOLEAN DEFAULT TRUE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_formule_trouvee = FALSE;

    SELECT f.prix_base,
           f.duree_gratuite_minutes,
           f.tarif_par_tranche,
           f.supplement_electrique,
           (c.libelle = 'electrique')
      INTO v_prix_base,
           v_duree_gratuite,
           v_tarif_tranche,
           v_supplement_electrique,
           v_est_electrique
      FROM abonnement a
      JOIN formule f ON f.id_formule = a.id_formule
      JOIN velo v ON v.numero_serie = p_numero_serie
      JOIN categorie_velo c ON c.id_categorie = v.id_categorie
     WHERE a.id_usager = p_id_usager
       AND DATE(p_date_depart) BETWEEN a.date_debut AND a.date_fin
     ORDER BY a.date_debut DESC
     LIMIT 1;

    IF NOT v_formule_trouvee THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Aucune formule active pour ce trajet';
    END IF;

    SET v_duree_minutes = GREATEST(TIMESTAMPDIFF(MINUTE, p_date_depart, p_date_arrivee), 0);

    RETURN v_prix_base
         + CEILING(
               GREATEST(
                   CAST(v_duree_minutes AS SIGNED) - CAST(v_duree_gratuite AS SIGNED),
                   0
               ) / 30.0
           ) * v_tarif_tranche
         + IF(v_est_electrique, v_supplement_electrique, 0.00);
END$$

DROP TRIGGER IF EXISTS trg_trajet_au_restituer_velo$$

CREATE TRIGGER trg_trajet_au_restituer_velo
AFTER UPDATE ON trajet
FOR EACH ROW
BEGIN
    IF OLD.date_arrivee IS NULL AND NEW.date_arrivee IS NOT NULL THEN
        UPDATE velo
        SET etat = 'disponible',
            id_borne_actuelle = NEW.id_borne_arrivee
        WHERE numero_serie = NEW.numero_serie;

        IF NEW.cout_facture > 0 THEN
            UPDATE usager
            SET solde_portefeuille = solde_portefeuille - NEW.cout_facture
            WHERE id_usager = NEW.id_usager;

            INSERT INTO operation_portefeuille (nature, montant, id_usager, id_trajet)
            VALUES ('paiement_trajet', -NEW.cout_facture, NEW.id_usager, NEW.id_trajet);
        END IF;
    END IF;
END$$

DELIMITER ;
