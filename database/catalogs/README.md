# Číselníky dřevin

Základ dodal správce aplikace 9. 11. 2026: 551 názvů taxonů a 1 100 názvů kultivarů. Žádné přesné duplicity názvů po oříznutí mezer a sjednocení velikosti písmen. Nejde o úplnou taxonomickou revizi. Všech 551 původních záznamů má prázdné powo_id. Údaj o původu z Copilotu/POWO není doložen; základ se proto neoznačuje za ověřený export POWO. Před veřejnou distribucí celého základu zbývá prověřit původ a oprávnění k jeho distribuci.

Kultivary jsou historický seznam samostatných názvů, bez vazby na druh. Stejné označení může patřit více druhům. Nevytváříme automaticky taxonomické vazby a netvrdíme, že byly kultivary ověřeny v POWO. Původní názvy a poznámky se zachovávají.

Nová instalace načte baseline.sql a additions.sql. Pro existující databázi lze oba soubory použít jako doplňující SQL: pouze chybějící názvy, žádné DELETE, REPLACE, ALTER ani přepis existujících hodnot. ID přiděluje cílová databáze; původní ID se nemění. SQL spouštějte bez souběžné editace číselníků. Toto není migrace objektů ani jejich historie.

Doplnění obsahuje šest samostatně ověřených vědeckých názvů a autorů; česká jména nejsou odvozována z angličtiny. Nejde o úplný seznam světových dřevin. Zdroj: Plants of the World Online, Royal Botanic Gardens, Kew, kontrolováno 9. 11. 2026:

- Acer opalus Mill. — https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:781424-1
- Acer monspessulanum L. — https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:781402-1
- Celtis australis L. — https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:850999-1
- Gymnocladus dioicus (L.) K.Koch — https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:1195073-2
- Parrotia persica (DC.) C.A.Mey. — https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:430741-1
- Heptacodium miconioides Rehder — https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:148517-1

Při zkoušce v cílové databázi se ukázala kolize `Krejčí` / `Krejci`: výchozí utf8mb4_unicode_ci nerozlišuje tuto diakritiku. Soubor zachovává oba původní záznamy, ale do nové databáze vloží první variantu; druhou považuje databáze za existující. Nová instalace tedy obsahuje 557 taxonů a 1 099 názvů kultivarů. V existující databázi se nic nemaže ani neslučuje; porovnání respektuje její collation.
