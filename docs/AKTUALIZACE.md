> Historický dokument starší etapy. Aktuální instalace a nastavený veřejný zdroj aktualizací jsou popsány v docs/INSTALACE.md; licence je GPL-3.0-or-later (LICENSING.md). Aktuální původ taxonomie rozlišuje database/catalogs/PROVENANCE.md. Níže uvedená tvrzení o dosud nevydaných balíčcích nebo nezvolené licenci popisují tehdejší stav, nikoli stav 2.25.4.

# Aktualizace aplikace — V2.23.0

## Co se aktualizuje
Každá instalace má vlastní databázi a fotografie. GitHub obsahuje pouze veřejná vydání programu. Aplikace stahuje informace o vydání a balíček; neposílá na GitHub projekty ani přístupové údaje. GitHub při připojení vidí obvyklá síťová metadata, například IP adresu hostingu.

Nová sekce Dashboard → Nastavení systému → Aktualizace aplikace je dostupná pouze aktivnímu správci systému. Správce jednotlivého projektu k ní nemá přístup.

## Jednorázové nastavení vydavatele
V private/config.php doplňte položku updates podle config.example.php:

- repository: skutečné `vlastník/repozitář` na GitHubu; veřejný repozitář.
- public_key: veřejný PEM klíč vydavatele. Lze použít `file_get_contents(__DIR__.'/publisher-public.pem')` a veřejný klíč uložit do private/publisher-public.pem.
- check_on_dashboard: true pro kontrolu při návštěvě dashboardu správce (výsledek se uchovává 6 hodin); false pro ruční kontrolu.

Repozitář ani klíč nejsou v balíčku předstíraně vyplněny. Je nutné je nastavit, až bude založen skutečný vydavatelský repozitář. Bez nich zůstává aplikace funkční, ale kontrola vydání či ověření balíčku nebudou dostupné.

Kontrola používá raw.githubusercontent.com/OWNER/REPO/main/release.json. Soubor uvádí version, notes, url a sha256. URL balíčku musí směřovat do Releases stejného repozitáře. ZIP je navíc kryptograficky podepsán; samotný kontrolní součet k instalaci nestačí.

## Průběh
1. Správce dostane na dashboardu upozornění nebo otevře Aktualizace aplikace a zvolí kontrolu vydání.
2. Zvolí Stáhnout a ověřit balíček, případně nahraje podepsaný ZIP ručně.
3. Zobrazí se verze, změny, počty souborů a migrací. Zatím se nic neinstaluje.
4. Správce zálohuje databázi, private/config.php, private/storage a fotografie v map/public/photos a uploads. Záloha databáze není vytvářena automaticky.
5. Zaškrtne potvrzení zálohy a souhlas s instalací konkrétního balíčku. Teprve tlačítko Schválit a nainstalovat spustí změny.
6. Aktualizátor získá výhradní zámek. Pokud právě probíhají jiné požadavky, odmítne spuštění bez změn; opakujte za chvíli. Při aktualizaci dostávají ostatní požadavky odpověď 503 s oznámením údržby.
7. Před údržbou ověří přípravu databáze (viz níže). Poté zálohuje nahrazované programové soubory, nahradí soubory a nakonec zapíše verzi. Úspěch ukáže správci. Nic se neinstaluje na pozadí bez schválení.

Podporovány jsou navazující balíčky z přesné verze na novější verzi. Přeskakování verzí je možné jen tehdy, když vydavatel připraví odpovídající souhrnný balíček. Návrat na nižší verzi se běžným aktualizátorem nepovoluje.

## Ochrana dat
Balíček nemůže zapisovat do private, složek fotografií, .git ani install. Verzi mění výhradně aktualizátor. Cesty s přechodem do nadřazeného adresáře, symbolické odkazy v ZIPu či cílové cestě a nesouhlasící kontrolní součty jsou odmítnuty. Soubory se nepřepisují před ověřením podpisu a kompletního obsahu.

### Databáze na sdíleném hostingu
Balíčky bez migrací neprovádějí žádný SQL příkaz. Pro balíčky s migracemi musí vydavatel přiložit podepsané kontroly požadovaných sloupců (tabulka, název, přesný COLUMN_TYPE a možnost NULL). Podporujeme pouze přidávání struktury kompatibilní s dosavadní verzí. Přesuny dat, mazání, přejmenování a jiné nevratné změny tímto postupem nevydávejte. Kontroly neověřují obsah dat, indexy, výchozí hodnoty ani obecnou bezpečnost SQL; za úplnost kontrol odpovídá vydavatel.

1. Správce zazálohuje databázi a přeruší práci uživatelů.
2. V připraveném balíčku zvolí **Stáhnout SQL přípravu**. Stažení ověřuje podpis a kontrolní součty, nic nespouští.
3. SQL spustí v phpMyAdminu ve správné databázi účtem správce databáze (na WEDOSu účet s oprávněním měnit strukturu). Heslo správce se do aplikace nezadává ani neukládá.
4. Po úspěšném provedení všech příkazů zvolí **Zkontrolovat databázi**. Při chybě SQL nepokračuje a řeší přípravu s vydavatelem. Skripty musí být opakovatelné.
5. Instalační formulář se zpřístupní po shodě požadované struktury. Před vlastní výměnou souborů se kontrola zopakuje pod aplikačním zámkem; při neshodě se údržba nezapne. Poté správce schválí instalaci a nakonec ověří funkčnost.

Starší balíčky s migracemi bez těchto kontrol jsou odmítnuté. Neobcházejte kontrolu odstraněním migrací z manifestu. Aktualizátor při žádné variantě nespouští DDL ani jiné migrační SQL.

## Přerušená aktualizace a obnova
Pokud dojde k chybě po zahájení změn, nemažte soubor údržby pouze proto, abyste web zpřístupnili. V private/updates/maintenance.json je označení použité zálohy. Podrobnost chyby je v PHP logu; last-result.json obsahuje výsledek posledního pokusu.

Postup správce přes FTP a správu databáze:
1. Ponechte údržbu aktivní a uchovejte současný stav pro diagnostiku.
2. Obnovte databázi ze zálohy pořízené před aktualizací. Pokud došlo ke změnám fotografií mimo aktualizaci, obnovte i příslušné soubory z odpovídající zálohy.
3. V private/updates/backup-DATUM-ID/files.json je seznam `files` a `existing`. Soubory uvedené v `existing` vraťte ze stejné záložní složky na odpovídající cestu aplikace, včetně app/VERSION. Soubor files.json do kořene aplikace nekopírujte. Nové soubory (`files` minus `existing`) odstraňte pouze podle tohoto seznamu. Alternativou je obnovení celé zálohy programových souborů.
4. Po obnovení konzistentních souborů a databáze odstraňte private/updates/maintenance.json a ověřte přihlášení, projekty, fotografie a export. Je-li aktivní OPcache, proveďte restart PHP nebo jeho vyprázdnění podle hostingu.

Automatický rollback databáze není součástí této verze. Zálohy a připravené ZIPy v private/updates se automaticky nemažou; po úspěšném ověření lze staré zálohy ručně archivovat mimo hosting a odstranit.

## Limity hostingu
PHP 8.2+, OpenSSL, ZipArchive, HTTPS odchozí spojení a zapisovatelné programové soubory a private. Limit ZIPu 30 MB, rozbaleného obsahu 100 MB, nejvýše 2500 položek. Hosting může mít nižší upload limit. Aktualizace jsou určeny pro jednu instalaci na jednom souborovém systému (nikoli více serverů s oddělenými disky).

Údržba chrání aplikační PHP požadavky, které používají společný bootstrap. Nejde o náhradu úplného auditu starších endpointů ani o zálohovací systém. Automatická kontrola GitHubu se dosud netestovala proti skutečnému vydavatelskému repozitáři, a není aktivovaná na produkci. Repozitář jirivis/stromy-v-mape je zatím soukromý; anonymní stahování vyžaduje veřejný zdroj vydání.


## Zdroj v administraci (od 2.25.2)
Správce systému může v Aktualizace → Nastavení zdroje aktualizací uložit
`vlastník/repozitář` a zapnout kontrolu při otevření dashboardu. Nastavení je
v `private/updates/source.json`; obsahuje jen repozitář a přepínač, nikdy klíč
ani heslo. Podpisový klíč se dál přebírá z původní konfigurace. Uložení zdroje
není ověření jeho dostupnosti; následuje Zkontrolovat vydání.

Distribuční repozitář musí být veřejný. Soukromý zdrojový repozitář může zůstat
soukromý; pro distribuci lze použít samostatný veřejný repozitář pouze s
release.json a podepsanými ZIPy. Konfigurace, databáze, fotografie ani soukromý
podpisový klíč se nezveřejňují. Samotný commit do zdrojového repozitáře není vydání.

Pro více výchozích verzí má release.json podobu `{"packages": [...]}`. Každá
položka obsahuje `from`, `version`, `url`, `sha256`, `notes` jako metadata
vygenerovaná build.py. Aktualizátor vybere nejvyšší novější verzi s přesně
odpovídajícím `from`. Uchovávejte i přechody pro starší instalace, aby mohly
postupovat po verzích. Legacy manifest bez `from` zůstává čitelný, přesnou
kompatibilitu však vždy kontroluje podepsaný manifest ZIPu před instalací.
Cache je vázána na repozitář a nainstalovanou verzi. Změna zdroje ji smaže.
Kontrola na dashboardu probíhá nejvýše jednou za šest hodin; nejde o vzdálené
spuštění instalace. Stažení i instalaci zahajuje správce, instalaci potvrzuje.

Před spuštěním distribuce: nahrát podepsaný ZIP do GitHub Release, vložit
odpovídající metadata do main/release.json a ověřit kontrolu i stažení bez
přihlášení na zkušební instalaci. Tento krok nelze nahradit lokálním testem.
