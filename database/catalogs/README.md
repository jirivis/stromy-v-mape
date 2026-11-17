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

## Doplnění identifikátorů POWO

Celý původní seznam je porovnán s oficiálním **WCVP v16 (4. 6. 2026)**, taxonomickým základem POWO. Zdroj: Govaerts, R.; Board of Trustees of the Royal Botanic Gardens, Kew. DOI https://doi.org/10.34885/egs6-cp24, licence [CC BY 3.0](https://creativecommons.org/licenses/by/3.0/). Staženo 9. 11. 2026 z https://sftp.kew.org/pub/data-repositories/WCVP/wcvp.zip. SHA-256 je uložen ve výsledném JSON. Jde o výběr a párování z tohoto vydání, nikoli záruku shody s každodenní živou verzí POWO.

- `powo-input.json`: původní názvy a autoři, bez interních ID aplikace.
- `powo-reviewed.json`: 11 jednotlivě posouzených rozdílů v pravopisu, hybridním znaku či autorovi; neprobíhá fuzzy přiřazování.
- `powo-matches.json`: 551 výsledků, způsob shody, původní i zdrojový autor, taxonomický status, odkaz a přijatý taxon, pokud je uveden.
- `powo-ids.sql`: doplní pouze prázdné ID při přesné shodě původního názvu **i autora**. Vyplněná ID, názvy, autoři, interní ID a objekty se nemění. Spustit po základním/doplňujícím SQL; instalátor to provede automaticky.

473 položek se shoduje jménem i autorem (bez rozdílů v mezerách), 67 má jedinou shodu názvu, ale odlišného autora; 11 je samostatně posouzených. Rozdíly autorů jsou upozornění k další redakci, nikoli automatické opravy. Identifikátor ukazuje na odpovídající **původní jméno**, tedy i synonymum nebo neplatné jméno; nezaměňovat jej s ID přijatého taxonu. Například chybně psané Eleagnus × ebbingei odkazuje na Elaeagnus × ebbingei (status Illegitimate). Zpětně názvy stromů neměníme.

Reprodukce: `python release-tools/catalogs/match_powo.py /cesta/wcvp.zip`. Celý zdrojový ZIP se do repozitáře nepřidává. Údaje o původu původních českých názvů a kultivarů zůstávají neověřené. Doplnění ID není ověření českých jmen či kultivarů a nezavádí vazbu kultivar–druh. Kultivar u stromu zůstává volitelný.

## Kontrola původu 17. 11. 2026

Aktuální rozlišení doložených a nedoložených částí: PROVENANCE.md. Souhrn auditního JSON: provenance-audit.json. Seznam 67 rozdílů autorů a 11 individuálně posouzených případů: author-review.csv. Licence vlastní aplikace neznamená přelicencování těchto dat.
