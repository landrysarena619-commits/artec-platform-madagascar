SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `artec_platform`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `artec_platform`;

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

CREATE TABLE `alertes_auto` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operateur_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `valeur` DECIMAL(10,2) DEFAULT NULL,
  `seuil` DECIMAL(10,2) DEFAULT NULL,
  `traite` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_alerte_operateur_type_created` (`operateur_id`, `type`, `created_at`),
  CONSTRAINT `fk_alerte_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `operateur_id` INT UNSIGNED NOT NULL,
  `plateforme` ENUM('facebook','instagram','tiktok') NOT NULL,
  `post_id` VARCHAR(100) DEFAULT NULL,
  `texte` TEXT DEFAULT NULL,
  `likes` INT UNSIGNED DEFAULT 0,
  `commentaires` INT UNSIGNED DEFAULT 0,
  `partages` INT UNSIGNED DEFAULT 0,
  `vues` INT UNSIGNED DEFAULT 0,
  `date_post` DATETIME DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_social_operateur` (`operateur_id`),
  KEY `idx_plateforme` (`plateforme`),
  CONSTRAINT `fk_social_operateur` FOREIGN KEY (`operateur_id`) REFERENCES `operateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_hash` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `fk_reset_user` (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `operateurs` (`nom`, `slug`, `logo`, `description`, `couleur`, `site_web`) VALUES
('Orange Madagascar', 'orange', 'assets/img/orange.png', 'Operateur historique de telecommunications a Madagascar, filiale du groupe Orange.', '#FF7900', 'https://www.orange.mg'),
('Yas Madagascar', 'yas', 'assets/img/yas.png', 'Operateur mobile et fournisseur d''acces Internet, marque du groupe Telma.', '#00A651', 'https://www.yas.mg'),
('Airtel Madagascar', 'airtel', 'assets/img/airtel.png', 'Operateur de telephonie mobile et services mobiles, filiale du groupe Airtel Africa.', '#E40000', 'https://www.airtel.mg');