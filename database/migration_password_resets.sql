-- ============================================================
-- Migration : table pour les réinitialisations de mot de passe
-- À exécuter dans phpMyAdmin sur la base artec_platform
-- ============================================================

USE `artec_platform`;

CREATE TABLE IF NOT EXISTS `password_resets` (
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

-- Nettoyage automatique des vieux tokens (optionnel, peut être fait via cron)
-- DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1;