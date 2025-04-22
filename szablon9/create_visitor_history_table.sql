-- SQL to create a visitor history table
CREATE TABLE IF NOT EXISTS `visitor_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `visitor_id` VARCHAR(50) NOT NULL,
  `page` VARCHAR(255) NOT NULL,
  `referrer` TEXT,
  `user_agent` VARCHAR(255),
  `screen_resolution` VARCHAR(20),
  `language` VARCHAR(10),
  `ip_address` VARCHAR(45),
  `visit_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_visitor_id` (`visitor_id`),
  INDEX `idx_visit_time` (`visit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 