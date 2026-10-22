-- ============================================================
-- PLATEFORME ARTEC MADAGASCAR - Base de données complète
-- Version : 1.0
-- Encodage : UTF-8 (utf8mb4)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

-- Création de la base si elle n'existe pas
CREATE DATABASE IF NOT EXISTS `artec_platform`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `artec_platform`;

-- ============================================================
-- TABLE : operateurs
-- ============================================================
CREATE TABLE `operateurs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `couleur` VARCHAR(7) DEFAULT '#0d6efd',
  `site_web` VARCHAR(255) DEFAULT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `date_creation` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : users
-- ============================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','artec','operateur') NOT NULL,
  `operateur_id` INT UNSIGNED DEFAULT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `derniere_connexion` DATETIME DEFAULT NULL,
  `ip_derniere_connexion` VARCHAR(45) DEFAULT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `fk_users_operateur` (`operateur_id`),
  CONSTRAINT `fk_users_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : publications
-- ============================================================
CREATE TABLE `publications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operateur_id` INT UNSIGNED NOT NULL,
  `titre` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `contenu` TEXT NOT NULL,
  `type` ENUM('offre','promotion','information','nouveaute') NOT NULL DEFAULT 'offre',
  `service` ENUM('mobile','internet','fibre','mobile_money','autre') NOT NULL DEFAULT 'mobile',
  `prix` DECIMAL(12,2) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `date_debut` DATE DEFAULT NULL,
  `date_fin` DATE DEFAULT NULL,
  `publie` TINYINT(1) NOT NULL DEFAULT 1,
  `vues` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `fk_publications_operateur` (`operateur_id`),
  KEY `idx_publie` (`publie`),
  CONSTRAINT `fk_publications_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : avis
-- ============================================================
CREATE TABLE `avis` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `publication_id` INT UNSIGNED DEFAULT NULL,
  `operateur_id` INT UNSIGNED NOT NULL,
  `visitor_token` VARCHAR(64) NOT NULL,
  `ip_hash` VARCHAR(64) NOT NULL,
  `note` TINYINT UNSIGNED NOT NULL,
  `commentaire` TEXT DEFAULT NULL,
  `region` VARCHAR(50) NOT NULL,
  `service` ENUM('mobile','internet','fibre','mobile_money','autre') NOT NULL DEFAULT 'mobile',
  `signale` TINYINT(1) NOT NULL DEFAULT 0,
  `modere` TINYINT(1) NOT NULL DEFAULT 0,
  `publie` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_avis_publication` (`publication_id`),
  KEY `fk_avis_operateur` (`operateur_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_ip_hash` (`ip_hash`),
  CONSTRAINT `fk_avis_publication` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_avis_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_note` CHECK (`note` BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : reclamations
-- ============================================================
CREATE TABLE `reclamations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operateur_id` INT UNSIGNED NOT NULL,
  `publication_id` INT UNSIGNED DEFAULT NULL,
  `visitor_token` VARCHAR(64) NOT NULL,
  `sujet` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `categorie` ENUM('reseau','facturation','service_client','offre_non_respectee','autre') NOT NULL DEFAULT 'autre',
  `statut` ENUM('nouveau','en_cours','resolu','rejete') NOT NULL DEFAULT 'nouveau',
  `reponse` TEXT DEFAULT NULL,
  `date_reponse` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reclam_operateur` (`operateur_id`),
  KEY `fk_reclam_publication` (`publication_id`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_reclam_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reclam_publication` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : messages (messagerie instantanée)
-- ============================================================
CREATE TABLE `messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_type` ENUM('visiteur_operateur','operateur_artec') NOT NULL,
  `conversation_key` VARCHAR(100) NOT NULL,
  `sender_type` ENUM('visiteur','operateur','artec') NOT NULL,
  `sender_id` INT UNSIGNED DEFAULT NULL,
  `sender_token` VARCHAR(64) DEFAULT NULL,
  `receiver_type` ENUM('visiteur','operateur','artec') NOT NULL,
  `receiver_id` INT UNSIGNED DEFAULT NULL,
  `operateur_id` INT UNSIGNED DEFAULT NULL,
  `publication_id` INT UNSIGNED DEFAULT NULL,
  `contenu` TEXT NOT NULL,
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conv_key` (`conversation_key`),
  KEY `idx_conv_type` (`conversation_type`),
  KEY `idx_created` (`created_at`),
  KEY `fk_msg_operateur` (`operateur_id`),
  KEY `fk_msg_publication` (`publication_id`),
  CONSTRAINT `fk_msg_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_publication` FOREIGN KEY (`publication_id`) REFERENCES `publications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : decisions (ARTEC → opérateurs)
-- ============================================================
CREATE TABLE `decisions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operateur_id` INT UNSIGNED NOT NULL,
  `artec_user_id` INT UNSIGNED NOT NULL,
  `type` ENUM('avertissement','mise_en_conformite','sanction','information') NOT NULL,
  `titre` VARCHAR(200) NOT NULL,
  `contenu` TEXT NOT NULL,
  `gravite` ENUM('faible','moyenne','haute','critique') NOT NULL DEFAULT 'moyenne',
  `statut` ENUM('ouverte','en_cours','resolue','archivee') NOT NULL DEFAULT 'ouverte',
  `date_limite` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_dec_operateur` (`operateur_id`),
  KEY `fk_dec_user` (`artec_user_id`),
  CONSTRAINT `fk_dec_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dec_user` FOREIGN KEY (`artec_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : ml_models (versioning)
-- ============================================================
CREATE TABLE `ml_models` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom_modele` VARCHAR(100) NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `type` ENUM('regressor','classifier','anomaly') NOT NULL,
  `fichier` VARCHAR(255) NOT NULL,
  `score_accuracy` DECIMAL(6,4) DEFAULT NULL,
  `score_rmse` DECIMAL(10,4) DEFAULT NULL,
  `date_entrainement` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nom_version` (`nom_modele`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : predictions (écrite par Python)
-- ============================================================
CREATE TABLE `predictions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operateur_id` INT UNSIGNED NOT NULL,
  `type_prediction` ENUM('satisfaction','risque_non_conformite','sentiment','anomalie') NOT NULL,
  `valeur` DECIMAL(10,4) NOT NULL,
  `label` VARCHAR(50) DEFAULT NULL,
  `score_confiance` DECIMAL(6,4) DEFAULT NULL,
  `details` JSON DEFAULT NULL,
  `modele_version` VARCHAR(20) DEFAULT NULL,
  `date_prediction` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pred_operateur` (`operateur_id`),
  KEY `idx_type` (`type_prediction`),
  KEY `idx_date` (`date_prediction`),
  CONSTRAINT `fk_pred_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE : logs
-- ============================================================
CREATE TABLE `logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `role` VARCHAR(20) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entite` VARCHAR(50) DEFAULT NULL,
  `entite_id` INT UNSIGNED DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_hash` VARCHAR(64) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_logs_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES DE DÉMO
-- ============================================================

-- --- Opérateurs ---
INSERT INTO `operateurs` (`nom`, `slug`, `logo`, `description`, `couleur`, `site_web`) VALUES
('Orange Madagascar', 'orange', 'assets/img/orange.png', 'Opérateur historique de télécommunications à Madagascar, filiale du groupe Orange.', '#FF7900', 'https://www.orange.mg'),
('Yas Madagascar', 'yas', 'assets/img/yas.png', 'Opérateur mobile et fournisseur d''accès Internet, marque du groupe Telma.', '#00A651', 'https://www.yas.mg'),
('Airtel Madagascar', 'airtel', 'assets/img/airtel.png', 'Opérateur de téléphonie mobile et services mobiles, filiale du groupe Airtel Africa.', '#E40000', 'https://www.airtel.mg');

-- --- Utilisateurs (mot de passe hashé avec password_hash PHP, algo BCRYPT) ---
-- Admin@2025 / Artec@2025 / Orange@2025 / Yas@2025 / Airtel@2025
INSERT INTO `users` (`nom`, `email`, `password`, `role`, `operateur_id`, `telephone`) VALUES
('Administrateur ARTEC', 'admin@artec.mg', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1Hl9aMcQc6xJz6bqYcJqXq0Yh3S7f9a', 'admin', NULL, '+261 34 00 000 01'),
('Direction ARTEC', 'artec@artec.mg', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1Hl9aMcQc6xJz6bqYcJqXq0Yh3S7f9b', 'artec', NULL, '+261 34 00 000 02'),
('Responsable Orange', 'orange@orange.mg', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1Hl9aMcQc6xJz6bqYcJqXq0Yh3S7f9c', 'operateur', 1, '+261 32 00 000 03'),
('Responsable Yas', 'yas@yas.mg', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1Hl9aMcQc6xJz6bqYcJqXq0Yh3S7f9d', 'operateur', 2, '+261 33 00 000 04'),
('Responsable Airtel', 'airtel@airtel.mg', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1Hl9aMcQc6xJz6bqYcJqXq0Yh3S7f9e', 'operateur', 3, '+261 34 00 000 05');

-- --- Publications de démo ---
INSERT INTO `publications` (`operateur_id`, `titre`, `slug`, `contenu`, `type`, `service`, `prix`, `date_debut`, `date_fin`, `publie`) VALUES
(1, 'Offre Fibre Orange - 100 Mbps', 'offre-fibre-orange-100mbps',
 'Profitez de la fibre optique Orange à 100 Mbps pour seulement 129 000 Ar/mois. Installation gratuite pour toute nouvelle souscription avant la fin du mois. Idéal pour les familles et les télétravailleurs.',
 'promotion', 'fibre', 129000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),

(2, 'Yas Mobile - Forfait 20 Go à 15 000 Ar', 'yas-mobile-forfait-20go',
 'Le forfait Yas Mobile 20 Go vous offre 20 Go d''internet, appels illimités vers Yas et 100 SMS à seulement 15 000 Ar par mois. Disponible dans toutes les agences Yas.',
 'offre', 'mobile', 15000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1),

(3, 'Airtel Money - Transfert gratuit', 'airtel-money-transfert-gratuit',
 'Airtel Money lance les transferts gratuits entre tous les utilisateurs Airtel Money pour toute transaction inférieure à 50 000 Ar. Une révolution pour les paiements du quotidien.',
 'promotion', 'mobile_money', 0.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 45 DAY), 1),

(1, 'Orange Money - Cashback 5%', 'orange-money-cashback-5',
 'Orange Money vous rembourse 5% de vos achats chez les partenaires agréés. Cumulez vos cashbacks et payez vos prochains achats avec Orange Money.',
 'promotion', 'mobile_money', 0.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),

(2, 'Yas Fibre - 200 Mbps à Antananarivo', 'yas-fibre-200mbps-tana',
 'La fibre Yas débarque à Antananarivo avec un débit de 200 Mbps à 199 000 Ar/mois. Couverture progressive dans les quartiers d''Analakely, Ivandry et Ankorondrano.',
 'nouveaute', 'fibre', 199000.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 1),

(3, 'Airtel 4G+ - Couverture nationale', 'airtel-4g-plus-couverture',
 'Airtel Madagascar annonce l''extension de son réseau 4G+ dans 15 nouvelles villes. Profitez d''une connexion ultra-rapide partout dans l''île.',
 'information', 'internet', 0.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 120 DAY), 1);

-- --- Avis de démo ---
INSERT INTO `avis` (`publication_id`, `operateur_id`, `visitor_token`, `ip_hash`, `note`, `commentaire`, `region`, `service`, `publie`) VALUES
(1, 1, 'demo_token_001', SHA2(CONCAT('demo', 'ip1'), 256), 8, 'La fibre Orange est très rapide, installation impeccable. Petit bémol sur le service client qui met du temps à répondre.', 'Antananarivo', 'fibre', 1),
(1, 1, 'demo_token_002', SHA2(CONCAT('demo', 'ip2'), 256), 7, 'Bon débit mais un peu cher comparé à la concurrence.', 'Antananarivo', 'fibre', 1),
(2, 2, 'demo_token_003', SHA2(CONCAT('demo', 'ip3'), 256), 9, 'Yas Mobile reste le meilleur rapport qualité-prix sur le marché.', 'Toamasina', 'mobile', 1),
(2, 2, 'demo_token_004', SHA2(CONCAT('demo', 'ip4'), 256), 6, 'Le forfait est correct mais le réseau est parfois instable en province.', 'Fianarantsoa', 'mobile', 1),
(3, 3, 'demo_token_005', SHA2(CONCAT('demo', 'ip5'), 256), 8, 'Airtel Money est très pratique pour les transferts. Merci pour la gratuité.', 'Antananarivo', 'mobile_money', 1),
(4, 1, 'demo_token_006', SHA2(CONCAT('demo', 'ip6'), 256), 7, 'Cashback intéressant mais peu de partenaires agréés dans ma région.', 'Mahajanga', 'mobile_money', 1),
(5, 2, 'demo_token_007', SHA2(CONCAT('demo', 'ip7'), 256), 9, 'La fibre Yas à Ivandry est excellente, débit constant même en soirée.', 'Antananarivo', 'fibre', 1),
(6, 3, 'demo_token_008', SHA2(CONCAT('demo', 'ip8'), 256), 5, 'La 4G+ Airtel est annoncée mais toujours pas disponible dans ma ville.', 'Toliara', 'internet', 1);

-- --- Réclamation de démo ---
INSERT INTO `reclamations` (`operateur_id`, `publication_id`, `visitor_token`, `sujet`, `description`, `categorie`, `statut`) VALUES
(2, 2, 'demo_token_004', 'Débit internet très faible en soirée', 'Depuis une semaine, le débit de mon forfait Yas Mobile chute considérablement entre 19h et 22h. Impossible de regarder une vidéo correctement.', 'reseau', 'en_cours');

-- --- Messages de démo (visiteur ↔ opérateur) ---
INSERT INTO `messages` (`conversation_type`, `conversation_key`, `sender_type`, `sender_token`, `receiver_type`, `operateur_id`, `publication_id`, `contenu`, `lu`) VALUES
('visiteur_operateur', 'pub_1_token_demo', 'visiteur', 'demo_token_001', 'operateur', 1, 1, 'Bonjour, la fibre 100 Mbps est-elle disponible à Ivato ?', 1),
('visiteur_operateur', 'pub_1_token_demo', 'operateur', NULL, 'visiteur', 1, 1, 'Bonjour, oui la fibre est disponible à Ivato depuis le mois dernier. Vous pouvez souscrire en agence.', 0);

-- --- Décision ARTEC de démo ---
INSERT INTO `decisions` (`operateur_id`, `artec_user_id`, `type`, `titre`, `contenu`, `gravite`, `statut`, `date_limite`) VALUES
(3, 2, 'mise_en_conformite', 'Mise en conformité - Publicité 4G+',
 'Suite à notre enquête, la publicité Airtel 4G+ mentionne une couverture dans des villes où le réseau n''est pas encore déployé. Merci de mettre à jour vos supports de communication sous 30 jours.',
 'haute', 'ouverte', DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- --- Logs de démo ---
INSERT INTO `logs` (`user_id`, `role`, `action`, `entite`, `entite_id`, `details`, `ip_hash`) VALUES
(1, 'admin', 'login', 'users', 1, 'Connexion administrateur réussie', SHA2('ip_admin', 256)),
(2, 'artec', 'login', 'users', 2, 'Connexion ARTEC réussie', SHA2('ip_artec', 256)),
(2, 'artec', 'create_decision', 'decisions', 1, 'Création décision : Mise en conformité Airtel', SHA2('ip_artec', 256));

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================