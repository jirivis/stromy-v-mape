SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `ico` varchar(20) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `note` varchar(1000) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `cultivars`
--

CREATE TABLE IF NOT EXISTS `cultivars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_reset_token_hash` (`token_hash`),
  KEY `idx_password_reset_user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE IF NOT EXISTS `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_ico` varchar(20) DEFAULT NULL,
  `client_address` varchar(500) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `client_phone` varchar(50) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `visibility` enum('private','public') DEFAULT 'private',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `idx_projects_client_id` (`client_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `project_users`
--

CREATE TABLE IF NOT EXISTS `project_users` (
  `project_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_role` enum('owner','admin','editor','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`project_id`,`user_id`),
  KEY `idx_project_users_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `species`
--

CREATE TABLE IF NOT EXISTS `species` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scientific_name` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `czech_name` varchar(255) DEFAULT NULL,
  `family` varchar(255) DEFAULT NULL,
  `genus` varchar(255) DEFAULT NULL,
  `powo_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `scientific_name` (`scientific_name`),
  KEY `idx_scientific_name` (`scientific_name`),
  KEY `idx_czech_name` (`czech_name`),
  KEY `idx_genus` (`genus`),
  KEY `idx_family` (`family`),
  FULLTEXT KEY `ft_species` (`scientific_name`,`czech_name`,`genus`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `svm_areas`
--

CREATE TABLE IF NOT EXISTS `svm_areas` (
  `valuation_selected` tinyint(1) NOT NULL DEFAULT 0,
  `valuation_value_czk` decimal(14,0) DEFAULT NULL,
  `valuation_method` varchar(80) DEFAULT NULL,
  `valuation_at` datetime DEFAULT NULL,
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `code` varchar(40) NOT NULL,
  `geometry_json` mediumtext NOT NULL,
  `attributes_json` mediumtext NOT NULL,
  `area_m2` double NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_code` (`project_id`,`code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `svm_area_history`
--

CREATE TABLE IF NOT EXISTS `svm_area_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) NOT NULL,
  `area_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `snapshot_json` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `area_history` (`project_id`,`area_id`,`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `svm_area_photos`
--

CREATE TABLE IF NOT EXISTS `svm_area_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `area_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `original_name` varchar(200) NOT NULL,
  `mime` varchar(32) NOT NULL,
  `content` mediumblob DEFAULT NULL,
  `file_path` varchar(80) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `area_project` (`area_id`,`project_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `svm_form_settings`
--

CREATE TABLE IF NOT EXISTS `svm_form_settings` (
  `project_id` bigint(20) unsigned NOT NULL,
  `settings_json` mediumtext NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `svm_form_templates`
--

CREATE TABLE IF NOT EXISTS `svm_form_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `fields_json` mediumtext NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `owner_name` (`owner_user_id`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `svm_table_audit`
--

CREATE TABLE IF NOT EXISTS `svm_table_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `object_type` varchar(10) NOT NULL,
  `object_id` bigint(20) unsigned NOT NULL,
  `changes_json` mediumtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project_object` (`project_id`,`object_type`,`object_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `trees`
--

CREATE TABLE IF NOT EXISTS `trees` (
  `valuation_selected` tinyint(1) NOT NULL DEFAULT 0,
  `valuation_value_czk` decimal(14,0) DEFAULT NULL,
  `valuation_method` varchar(80) DEFAULT NULL,
  `valuation_at` datetime DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `usr_id` int(11) DEFAULT NULL,
  `species` varchar(255) DEFAULT NULL,
  `czech` varchar(255) DEFAULT NULL,
  `dbh1_cm` int(11) DEFAULT NULL,
  `dbh2_cm` int(11) DEFAULT NULL,
  `dbh3_cm` int(11) DEFAULT NULL,
  `dbh4_cm` int(11) DEFAULT NULL,
  `girth1_cm` int(11) DEFAULT NULL,
  `girth2_cm` int(11) DEFAULT NULL,
  `girth3_cm` int(11) DEFAULT NULL,
  `girth4_cm` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `canopy_h_m` int(11) DEFAULT NULL,
  `spread_m` int(11) DEFAULT NULL,
  `phy_age` tinyint(4) DEFAULT NULL,
  `age` smallint(6) DEFAULT NULL,
  `vitality` tinyint(4) DEFAULT NULL,
  `tree_health` tinyint(4) DEFAULT NULL,
  `stability` tinyint(4) DEFAULT NULL,
  `operational_safety` tinyint(4) DEFAULT NULL,
  `longevity` char(1) DEFAULT NULL,
  `ecological_value` tinyint(4) DEFAULT NULL,
  `landscape_value` tinyint(4) DEFAULT NULL,
  `note_tree` text DEFAULT NULL,
  `treatment_u0` varchar(255) DEFAULT NULL,
  `costs_tu0` int(255) DEFAULT NULL,
  `treatment_u1` varchar(255) DEFAULT NULL,
  `costs_tu1` int(255) DEFAULT NULL,
  `treatment_u2` varchar(255) DEFAULT NULL,
  `costs_tu2` int(255) DEFAULT NULL,
  `treatment_u3` varchar(255) DEFAULT NULL,
  `costs_tu3` int(255) DEFAULT NULL,
  `costs_total` int(11) GENERATED ALWAYS AS (ifnull(`costs_tu0`,0) + ifnull(`costs_tu1`,0) + ifnull(`costs_tu2`,0) + ifnull(`costs_tu3`,0)) STORED,
  `note_treatment` text DEFAULT NULL,
  `treatment_history` text DEFAULT NULL,
  `angio_gymno` varchar(50) DEFAULT NULL,
  `cultivated_variety` varchar(255) DEFAULT NULL,
  `scientific_author` varchar(255) DEFAULT NULL,
  `planted` date DEFAULT NULL,
  `measured_at` date DEFAULT NULL,
  `last_insp_at` date DEFAULT NULL,
  `next_insp_at` date DEFAULT NULL,
  `lat_wgs84` decimal(10,7) DEFAULT NULL,
  `lng_wgs84` decimal(10,7) DEFAULT NULL,
  `x_sjtsk` decimal(10,3) DEFAULT NULL,
  `y_sjtsk` decimal(10,3) DEFAULT NULL,
  `note_public` text DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `status` enum('active','removed') NOT NULL DEFAULT 'active',
  `removed_at` date DEFAULT NULL,
  `death_date` date DEFAULT NULL,
  `removal_date` date DEFAULT NULL,
  `death_marked_at` datetime DEFAULT NULL,
  `removal_marked_at` datetime DEFAULT NULL,
  `removal_type` enum('felled','dead','fallen','missing','other') DEFAULT NULL,
  `removal_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trees_project_id` (`project_id`),
  KEY `idx_trees_project_usr_id` (`project_id`,`usr_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `trees_photo`
--

CREATE TABLE IF NOT EXISTS `trees_photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tree_id` int(11) DEFAULT NULL,
  `tree_usr_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `note_photo` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `tree_id` (`tree_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tree_history`
--

CREATE TABLE IF NOT EXISTS `tree_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tree_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `snapshot_at` datetime NOT NULL DEFAULT current_timestamp(),
  `snapshot_by_user_id` int(11) DEFAULT NULL,
  `event_type` enum('manual','inspection','update','removed','restored') NOT NULL DEFAULT 'manual',
  `history_note` text DEFAULT NULL,
  `tree_data` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tree_history_tree` (`tree_id`),
  KEY `idx_tree_history_project` (`project_id`),
  KEY `idx_tree_history_date` (`snapshot_at`),
  KEY `idx_tree_history_user` (`snapshot_by_user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tree_history_photos`
--

CREATE TABLE IF NOT EXISTS `tree_history_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `history_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tree_history_photos_history` (`history_id`),
  KEY `idx_tree_history_photos_photo` (`photo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tree_measurements`
--

CREATE TABLE IF NOT EXISTS `tree_measurements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tree_id` bigint(20) unsigned NOT NULL,
  `measurement_type` enum('dbh','girth') NOT NULL,
  `value_cm` decimal(8,2) NOT NULL,
  `measured_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tree_measurements_tree` (`tree_id`),
  KEY `idx_tree_measurements_history` (`tree_id`,`measurement_type`,`measured_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `tree_shares`
--

CREATE TABLE IF NOT EXISTS `tree_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tree_id` int(11) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_access_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tree_shares_token_hash` (`token_hash`),
  KEY `idx_tree_shares_tree_id` (`tree_id`),
  KEY `fk_tree_shares_created_by` (`created_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor','client','viewer') DEFAULT 'client',
  `is_super_admin` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `user_invitations`
--

CREATE TABLE IF NOT EXISTS `user_invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_invitations_token_hash` (`token_hash`),
  KEY `idx_user_invitations_user_id` (`user_id`),
  KEY `fk_user_invitations_created_by` (`created_by`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `fk_projects_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `project_users`
--
ALTER TABLE `project_users`
  ADD CONSTRAINT `fk_project_users_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_project_users_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `trees`
--
ALTER TABLE `trees`
  ADD CONSTRAINT `fk_trees_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `trees_photo`
--
ALTER TABLE `trees_photo`
  ADD CONSTRAINT `trees_photo_ibfk_1` FOREIGN KEY (`tree_id`) REFERENCES `trees` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tree_shares`
--
ALTER TABLE `tree_shares`
  ADD CONSTRAINT `fk_tree_shares_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tree_shares_tree` FOREIGN KEY (`tree_id`) REFERENCES `trees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_invitations`
--
ALTER TABLE `user_invitations`
  ADD CONSTRAINT `fk_user_invitations_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_invitations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Jedinečnost přihlašovacího jména v nové instalaci.
ALTER TABLE users ADD UNIQUE KEY uq_users_username (username);
-- Public maps are opt-in; existing project visibility is deliberately ignored.
CREATE TABLE IF NOT EXISTS svm_public_maps (
 project_id INT NOT NULL PRIMARY KEY,
 token CHAR(48) NOT NULL UNIQUE,
 enabled TINYINT NOT NULL DEFAULT 0,
 fields_json TEXT NOT NULL,
 photos TINYINT NOT NULL DEFAULT 0,
 photo_notes TINYINT NOT NULL DEFAULT 0,
 reports TINYINT NOT NULL DEFAULT 0,
 email_notify TINYINT NOT NULL DEFAULT 0,
 last_mail_at DATETIME NULL,
 version INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS svm_public_objects (
 project_id INT NOT NULL,
 kind VARCHAR(5) NOT NULL,
 object_id INT NOT NULL,
 token CHAR(48) NOT NULL UNIQUE,
 enabled TINYINT NOT NULL DEFAULT 0,
 PRIMARY KEY(project_id,kind,object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS svm_public_reports (
 id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 project_id INT NOT NULL,
 kind VARCHAR(5) NOT NULL,
 object_id INT NOT NULL,
 object_code VARCHAR(100) NOT NULL,
 message TEXT NOT NULL,
 contact VARCHAR(200) NOT NULL DEFAULT '',
 status VARCHAR(12) NOT NULL DEFAULT 'new',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NULL,
 updated_by INT NULL,
 KEY project_status(project_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS svm_public_rate (
 bucket CHAR(64) NOT NULL PRIMARY KEY,
 hits INT NOT NULL DEFAULT 0,
 expires_at DATETIME NOT NULL,
 KEY expiry(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
