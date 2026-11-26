# Data odumření a odstranění

Pro stávající instalace je před nasazením tohoto kódu nutná migrace `database/migrations/09_tree_event_dates.sql`. Nové instalace mají sloupce v `database/schema.sql`. Migrace nepřepisuje ani nedoplňuje staré záznamy.

- `death_date`: známé skutečné datum odumření (volitelné).
- `removal_date`: známé skutečné datum odstranění / selhání (volitelné).
- `death_marked_at`: systémový čas přechodu na vitalitu 5 nebo označení „Odumřel“.
- `removal_marked_at`: systémový čas označení ostatního stavu odstranění.

Běžná editace označení neposouvá. Karta a popup upřednostní skutečné datum; jinak uvedou „Označeno jako … dne“. Historické údaje se nedopočítávají. Odumření samo ponechává strom aktivní, protože může stále stát. Obnovení odstraněného záznamu ruší datum odstranění; změna vitality z 5 ruší aktuální datum odumření, pokud zároveň není zvolen stav „Odumřel“. Předchozí údaje zůstávají v historii.

Veřejná mapa zpřístupní stav pouze podle výslovně vybraných polí (vitalita nebo stav jedince); skutečná data mají samostatné volby. Interní systémové časy se nezveřejňují.

Při přípravě dalšího aktualizačního balíčku zahrnout migraci i `09_tree_event_dates_checks.json`; nepublikovat jako aktualizaci bez databázových změn.
