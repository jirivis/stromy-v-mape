-- MariaDB: repeatable additive preparation; no valuation is calculated.
ALTER TABLE `trees`
  ADD COLUMN IF NOT EXISTS `valuation_selected` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `valuation_value_czk` decimal(14,0) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `valuation_method` varchar(80) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `valuation_at` datetime DEFAULT NULL;

ALTER TABLE `svm_areas`
  ADD COLUMN IF NOT EXISTS `valuation_selected` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `valuation_value_czk` decimal(14,0) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `valuation_method` varchar(80) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `valuation_at` datetime DEFAULT NULL;
