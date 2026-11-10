# Opravy 2.24.1

Aktualizace z 2.24.0 nemění databázi. Podepsaný ZIP se nahraje na stránce aktualizací; po ověření správce schválí instalaci. Lokální konfigurace a fotografie se nepřepisují.

- Fotografie v editačním formuláři i popupu používají přímo autorizovaný photo.php. Pravidla pro staré adresy již nevyžadují hostingem případně zakázané Options ExecCGI. Přístupová kontrola zůstává zachovaná; seznam adresáře a spustitelné soubory jsou zakázané.
- Popisky stromů 16 px, minimální symbol 28 px. Zvětšení zobrazení nemění rozměry uložené v databázi.
- OSM v hlavní mapě a náhledu GPKG zůstává dostupná do zoomu 22 zvětšením dlaždic zoomu 19. Další mapové formuláře toto nastavení již měly. Nejde o novou vektorovou vrstvu ani nový detail podkladu.
- Horní tlačítko Uložit změny používá stejnou validaci a ukládání jako spodní tlačítko. Opuštění rozepsaného formuláře potvrzuje dialog s logem. Při zavření celé záložky zůstává systémové potvrzení prohlížeče.
- Našeptávání od dvou znaků: krátké čekání 180 ms při psaní, rušení zastaralých dotazů, paměť posledních 50 dotazů po dobu otevření formuláře, informace o načítání/chybě. Druh ani kultivar se automaticky nepřiřazuje.
- Překlad DataTables je lokální; inicializace nečeká na externí JSON. Opožděné úpravy ignorují již nahrazenou instanci.

Ověření: Node regresní testy URL a rozměrů; kontrola syntaxe JS; sestavení a ověření podpisu balíčku; CI PHP/MariaDB/Apache včetně omezeného AllowOverride a instalace skutečného opravného ZIP se zachováním dat. Produkční potvrzení vyžaduje po instalaci otevřít starou i novou fotografii a ověřit formulář na WEDOS.

## Poznatky uživatele 10. listopadu

Fotografie ploch se správně nahrávají a na kartě zobrazují i s popiskem. Hlášený problém fotografií se týká stromů.

Číselný popisek plochy v hlavní mapě a mapě editoru dostává pouze pro zobrazení malé p (12 → p12). Starší kódy typu P1 se neduplikují. Tabulky, karty a uložené hodnoty se nemění. Opravy zatím pouze na GitHubu; nové vydání a nasazení počkají na další poznatky.
