-- Spustit po záloze databáze. Zachovává ID, řádky i dosavadní hodnoty.
-- Rozšíření podepsaného TINYINT na SMALLINT: stáří až 32767 let.
ALTER TABLE trees MODIFY COLUMN age SMALLINT NULL DEFAULT NULL;
