-- Chat messages table for ITS support
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(64) NOT NULL,
  `sender` VARCHAR(32) NOT NULL,
  `text` TEXT,
  `meta` JSON DEFAULT NULL,
  `received_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
