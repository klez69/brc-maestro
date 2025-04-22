-- SQL script to set up visitor tracking
-- Creates necessary tables for tracking active visitors and visitor history

-- Create active visitors table if it doesn't exist
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` VARCHAR(50) NOT NULL,
  `page` VARCHAR(255) NOT NULL,
  `referrer` TEXT,
  `user_agent` VARCHAR(255),
  `screen_resolution` VARCHAR(20),
  `language` VARCHAR(10),
  `ip_address` VARCHAR(45),
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create visitor history table if it doesn't exist
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

-- Create view for visitor statistics
CREATE OR REPLACE VIEW `visitor_statistics` AS
SELECT 
  DATE(visit_time) as visit_date,
  COUNT(*) as total_visits,
  COUNT(DISTINCT visitor_id) as unique_visitors,
  COUNT(DISTINCT ip_address) as unique_ips
FROM 
  visitor_history
GROUP BY 
  DATE(visit_time)
ORDER BY 
  visit_date DESC; 