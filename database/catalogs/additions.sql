-- Six names individually checked against POWO on 2026-11-09.
-- Czech names and families left unset pending separate verification.
INSERT INTO `species` (`scientific_name`,`author`,`genus`,`powo_id`) SELECT 'Acer opalus','Mill.','Acer','781424-1' WHERE NOT EXISTS (SELECT 1 FROM `species` WHERE `scientific_name` = 'Acer opalus');
INSERT INTO `species` (`scientific_name`,`author`,`genus`,`powo_id`) SELECT 'Acer monspessulanum','L.','Acer','781402-1' WHERE NOT EXISTS (SELECT 1 FROM `species` WHERE `scientific_name` = 'Acer monspessulanum');
INSERT INTO `species` (`scientific_name`,`author`,`genus`,`powo_id`) SELECT 'Celtis australis','L.','Celtis','850999-1' WHERE NOT EXISTS (SELECT 1 FROM `species` WHERE `scientific_name` = 'Celtis australis');
INSERT INTO `species` (`scientific_name`,`author`,`genus`,`powo_id`) SELECT 'Gymnocladus dioicus','(L.) K.Koch','Gymnocladus','1195073-2' WHERE NOT EXISTS (SELECT 1 FROM `species` WHERE `scientific_name` = 'Gymnocladus dioicus');
INSERT INTO `species` (`scientific_name`,`author`,`genus`,`powo_id`) SELECT 'Parrotia persica','(DC.) C.A.Mey.','Parrotia','430741-1' WHERE NOT EXISTS (SELECT 1 FROM `species` WHERE `scientific_name` = 'Parrotia persica');
INSERT INTO `species` (`scientific_name`,`author`,`genus`,`powo_id`) SELECT 'Heptacodium miconioides','Rehder','Heptacodium','148517-1' WHERE NOT EXISTS (SELECT 1 FROM `species` WHERE `scientific_name` = 'Heptacodium miconioides');
