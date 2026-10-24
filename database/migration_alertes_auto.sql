USE `artec_platform`;

CREATE TABLE IF NOT EXISTS `alertes_auto` (
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
