-- ============================================================
--  schema.sql — Site e-commerce Cosmétique
--  À importer dans XAMPP via phpMyAdmin ou la CLI MySQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS cosmetique_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cosmetique_db;

-- -------------------------------------------------------
-- 1. UTILISATEURS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(100)    NOT NULL,
    prenom          VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    motDePasse      VARCHAR(255)    NOT NULL,
    role            ENUM('client','administrateur') NOT NULL DEFAULT 'client',
    adresse         TEXT            NULL,
    telephone       VARCHAR(20)     NULL,
    actif           TINYINT(1)      NOT NULL DEFAULT 1,
    dateInscription DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. CATÉGORIES
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    idCategorie INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(150) NOT NULL,
    description TEXT         NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 3. PRODUITS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS produits (
    idProduit   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(200)        NOT NULL,
    description TEXT                NULL,
    prix        DECIMAL(10,2)       NOT NULL,
    stock       INT UNSIGNED        NOT NULL DEFAULT 0,
    image       VARCHAR(300)        NULL,
    idCategorie INT UNSIGNED        NOT NULL,
    CONSTRAINT fk_produit_categorie
        FOREIGN KEY (idCategorie) REFERENCES categories(idCategorie)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 4. PANIERS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS paniers (
    idPanier  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idClient  INT UNSIGNED   NOT NULL,
    total     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    dateMaj   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_panier_client
        FOREIGN KEY (idClient) REFERENCES utilisateurs(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 5. LIGNES PANIER
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS lignes_panier (
    idLignePanier INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idPanier      INT UNSIGNED   NOT NULL,
    idProduit     INT UNSIGNED   NOT NULL,
    quantite      INT UNSIGNED   NOT NULL DEFAULT 1,
    sousTotal     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_lp_panier
        FOREIGN KEY (idPanier)  REFERENCES paniers(idPanier)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lp_produit
        FOREIGN KEY (idProduit) REFERENCES produits(idProduit)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 6. COMMANDES
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS commandes (
    idCommande    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idClient      INT UNSIGNED   NOT NULL,
    dateCommande  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montantTotal  DECIMAL(10,2)  NOT NULL,
    statut        ENUM('en attente','validée','expédiée','livrée','annulée')
                  NOT NULL DEFAULT 'en attente',
    CONSTRAINT fk_commande_client
        FOREIGN KEY (idClient) REFERENCES utilisateurs(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 7. LIGNES COMMANDE
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS lignes_commande (
    idLigneCommande INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idCommande      INT UNSIGNED   NOT NULL,
    idProduit       INT UNSIGNED   NOT NULL,
    quantite        INT UNSIGNED   NOT NULL DEFAULT 1,
    prixUnitaire    DECIMAL(10,2)  NOT NULL,
    CONSTRAINT fk_lc_commande
        FOREIGN KEY (idCommande) REFERENCES commandes(idCommande)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lc_produit
        FOREIGN KEY (idProduit)  REFERENCES produits(idProduit)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 8. PAIEMENTS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS paiements (
    idPaiement     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idCommande     INT UNSIGNED   NOT NULL,
    datePaiement   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    montant        DECIMAL(10,2)  NOT NULL,
    modePaiement   VARCHAR(50)    NOT NULL DEFAULT 'carte',
    statutPaiement ENUM('en attente','validé','refusé','remboursé')
                   NOT NULL DEFAULT 'en attente',
    CONSTRAINT fk_paiement_commande
        FOREIGN KEY (idCommande) REFERENCES commandes(idCommande)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 9. AVIS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS avis (
    idAvis      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idProduit   INT UNSIGNED NOT NULL,
    idClient    INT UNSIGNED NOT NULL,
    note        TINYINT UNSIGNED NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT NULL,
    dateAvis    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avis_produit
        FOREIGN KEY (idProduit) REFERENCES produits(idProduit)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_avis_client
        FOREIGN KEY (idClient)  REFERENCES utilisateurs(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 10. FAVORIS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS favoris (
    idFavori   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idClient   INT UNSIGNED NOT NULL,
    idProduit  INT UNSIGNED NOT NULL,
    dateAjout  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favori (idClient, idProduit),
    CONSTRAINT fk_favori_client
        FOREIGN KEY (idClient)  REFERENCES utilisateurs(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_favori_produit
        FOREIGN KEY (idProduit) REFERENCES produits(idProduit)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 11. PROMOTIONS
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS promotions (
    idPromotion   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idProduit     INT UNSIGNED NOT NULL,
    typeReduction ENUM('pourcentage','montant') NOT NULL DEFAULT 'pourcentage',
    valeur        DECIMAL(10,2) NOT NULL,
    dateDebut     DATE          NOT NULL,
    dateFin       DATE          NOT NULL,
    actif         TINYINT(1)    NOT NULL DEFAULT 1,
    CONSTRAINT fk_promo_produit
        FOREIGN KEY (idProduit) REFERENCES produits(idProduit)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  DONNÉES DE TEST
-- ============================================================

-- Catégories
INSERT INTO categories (nom, description) VALUES
('Soins visage',   'Crèmes, sérums, masques et soins hydratants pour le visage.'),
('Soins corps',    'Lotions, huiles et gommages pour une peau douce et nourrie.'),
('Maquillage',     'Fond de teint, rouges à lèvres, mascaras et palettes d\'ombres.'),
('Parfums',        'Eaux de parfum et eaux de toilette pour femmes et hommes.'),
('Soins cheveux',  'Shampoings, après-shampoings et masques capillaires.');

-- Produits
INSERT INTO produits (nom, description, prix, stock, image, idCategorie) VALUES
-- Soins visage (1)
('Sérum Vitamine C Éclat',        'Sérum concentré en vitamine C pure pour unifier et illuminer le teint. Texture légère, absorption rapide.',                              29.90,  45, 'serum-vitc.jpg',       1),
('Crème Hydratante Légère 24h',   'Formule douce enrichie en acide hyaluronique et aloe vera. Convient aux peaux sensibles et mixtes.',                                  22.50,  60, 'creme-hydra.jpg',      1),
('Masque Purifiant Argile Verte',  'Masque désincrustant à l\'argile verte et à l\'huile de tea tree. Pores visiblement resserrés en 10 minutes.',                       18.00,  30, 'masque-argile.jpg',    1),
-- Soins corps (2)
('Huile Sèche Corps Argan & Rose','Huile précieuse non grasse qui s\'absorbe instantanément. Peau soyeuse et parfumée après chaque application.',                        34.00,  25, 'huile-argan.jpg',      2),
('Gommage Sucre & Miel',          'Gommage doux aux cristaux de sucre de canne et au miel de Manuka. Idéal pour préparer la peau avant une hydratation intensive.',      16.50,  40, 'gommage-sucre.jpg',    2),
-- Maquillage (3)
('Fond de Teint Satin SPF 20',    'Fond de teint longue tenue à fini satiné. Bonne couvrance, formule enrichie en SPF 20. Disponible en 12 teintes.',                  38.00,  55, 'fond-teint.jpg',       3),
('Rouge à Lèvres Mat Prune Profond','Rouge à lèvres mat ultra-pigmenté teinte Prune Profond. Tient 8h sans dessécher les lèvres grâce au complexe beurre de karité.',   24.90,  70, 'rouge-levres.jpg',     3),
-- Parfums (4)
('Eau de Parfum Oud & Jasmin',    'Fragrance boisée-florale avec des notes de tête citronnées, un cœur de jasmin et un fond d\'oud crémeux. 50 ml.',                   89.00,  15, 'parfum-oud.jpg',       4),
('Eau de Toilette Musc Blanc',    'Musc blanc léger et enveloppant, idéal au quotidien. Notes de vanille et de bois de santal. 100 ml.',                                49.90,  20, 'parfum-musc.jpg',      4),
-- Soins cheveux (5)
('Masque Nutrition Intense Kératine','Masque capillaire riche en kératine végétale. Répare les pointes abîmées et apporte brillance et souplesse. 300 ml.',              21.00,  35, 'masque-keratine.jpg',  5);

-- Utilisateurs (mots de passe de test en clair, hachés avec password_hash() / bcrypt) :
--   Client Marie Dupont   : marie.dupont@example.com  / Client123!
--   Client Julien Martin  : julien.martin@example.com / Client123!
--   Admin  Site           : admin@cosmetique.fr        / Admin123!
INSERT INTO utilisateurs (nom, prenom, email, motDePasse, role, adresse, telephone) VALUES
('Dupont',   'Marie',   'marie.dupont@example.com',  '$2b$12$STl5EzmJ4KfCGgXai/CthOR6vOsBWhcvJPLN69GgVphhNHO5bR4PW', 'client',         '12 rue des Fleurs, 75008 Paris',          '0612345678'),
('Martin',   'Julien',  'julien.martin@example.com', '$2b$12$STl5EzmJ4KfCGgXai/CthOR6vOsBWhcvJPLN69GgVphhNHO5bR4PW', 'client',         '5 avenue Victor Hugo, 69002 Lyon',        '0623456789'),
('Admin',    'Site',    'admin@cosmetique.fr',        '$2b$12$.5UQocH8/HOgynyM.gcdxuesTMlCnO4E3SIEvKDzH5qayN1NLFOO6',  'administrateur', NULL,                                      NULL);

-- Avis
INSERT INTO avis (idProduit, idClient, note, commentaire) VALUES
(1, 1, 5, 'Incroyable ! Mon teint est visiblement plus lumineux après 2 semaines d\'utilisation.'),
(2, 2, 4, 'Texture agréable et hydratation longue durée. Je recommande pour les peaux mixtes.'),
(6, 1, 5, 'Tenue parfaite toute la journée, fini naturel. La couvrance est vraiment top.');

-- Favoris
INSERT INTO favoris (idClient, idProduit) VALUES
(1, 1),
(1, 4),
(2, 7),
(2, 8);

-- Promotion test (10 % sur le sérum vitamine C)
INSERT INTO promotions (idProduit, typeReduction, valeur, dateDebut, dateFin, actif) VALUES
(1, 'pourcentage', 10.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
(6, 'montant',      5.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 1);
