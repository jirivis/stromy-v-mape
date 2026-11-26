-- Actual event dates and system marking times; no historical backfill.
ALTER TABLE trees
  ADD COLUMN IF NOT EXISTS `death_date` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `removal_date` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `death_marked_at` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `removal_marked_at` datetime DEFAULT NULL;
