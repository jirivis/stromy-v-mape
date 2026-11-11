# Opravy 2.24.1

Aktualizace z 2.24.0 nemění databázi. Podepsaný ZIP se nahraje na stránce aktualizací; po ověření správce schválí instalaci. Lokální konfigurace a fotografie se nepřepisují.

- Fotografie v editačním formuláři i popupu používají přímo autorizovaný photo.php. Pravidla pro staré adresy již nevyžadují hostingem případně zakázané Options ExecCGI. Přístupová kontrola zůstává zachovaná; seznam adresáře a spustitelné soubory jsou zakázané.
- Popisky stromů na počítači 16 px, minimální symbol 28 px; na dotykových zařízeních zůstává 13 px a 20 px. Zvětšení zobrazení nemění rozměry uložené v databázi.
- OSM v hlavní mapě a náhledu GPKG zůstává dostupná do zoomu 22 zvětšením dlaždic zoomu 19. Další mapové formuláře toto nastavení již měly. Nejde o novou vektorovou vrstvu ani nový detail podkladu.
- Horní tlačítko Uložit změny používá stejnou validaci a ukládání jako spodní tlačítko. Opuštění rozepsaného formuláře potvrzuje dialog s logem. Při zavření celé záložky zůstává systémové potvrzení prohlížeče.
- Našeptávání od dvou znaků: krátké čekání 180 ms při psaní, rušení zastaralých dotazů, paměť posledních 50 dotazů po dobu otevření formuláře, informace o načítání/chybě. Druh ani kultivar se automaticky nepřiřazuje.
- Překlad DataTables je lokální; inicializace nečeká na externí JSON. Opožděné úpravy ignorují již nahrazenou instanci.

Ověření: Node regresní testy URL a rozměrů; kontrola syntaxe JS; sestavení a ověření podpisu balíčku; CI PHP/MariaDB/Apache včetně omezeného AllowOverride a instalace skutečného opravného ZIP se zachováním dat. Produkční potvrzení vyžaduje po instalaci otevřít starou i novou fotografii a ověřit formulář na WEDOS.

## Poznatky uživatele 10. listopadu

Fotografie ploch se správně nahrávají a na kartě zobrazují i s popiskem. Hlášený problém fotografií se týká stromů.

Číselný popisek plochy v hlavní mapě a mapě editoru dostává pouze pro zobrazení malé p (12 → p12). Starší kódy typu P1 se neduplikují. Tabulky, karty a uložené hodnoty se nemění. Opravy zatím pouze na GitHubu; nové vydání a nasazení počkají na další poznatky.

Sdílení karty stromu: odstraněno pole e-mailu zákazníka, po vytvoření odkazu jsou dostupné E-mailem, WhatsApp, kopírování a systémové Sdílet (pokud je prohlížeč podporuje). Poznámka k odkazu zůstává. Zprávu odesílá uživatel ve zvolené aplikaci; systém neposílá poštu jeho jménem. Kopírování již nehlásí úspěch, pokud selže i náhradní způsob.


Export: odstraněny HTML galerie i jejich odkazy z QGISu. Zachovány hlavní fotografie, relační tabulky fotografií a samostatné textové popisky. Nová výchozím stavem vypnutá volba „Přidat popisky fotek pod exportované fotografie“ vytváří JPEG kopie (kvalita 95) s bílým pruhem pod celým snímkem, českou diakritikou, zalamováním textu a zohledněním EXIF orientace. Fotografie bez popisku se nekonvertují; originály na serveru se nemění. Vyžaduje GD/FreeType a pro JPEG EXIF, při nedostatečné paměti se export ukončí s vysvětlením.

## Sjednocené filtry

Hlavní mapa, její spodní seznam a Tabulka projektu používají stejný panel a vyhodnocení. Obsahuje hledání ve sloupcích, podmínky všechny/alespoň jedna, číselné porovnání, prázdné hodnoty i dosavadní rychlé filtry naléhavosti, kontrol, odstraněných stromů, kryto/naho a stavu ploch. Stav se pamatuje zvlášť podle uživatele, projektu a geometrie v tomto prohlížeči; změny se přenesou i mezi otevřenými záložkami. Výběr checkboxy zůstává nezávislý. Zrušit všechny filtry vrací i rychlé filtry a hledání. Rozpracované podmínky se aplikují až tlačítkem Použít podmínky.

Mapový GeoJSON nese navíc původní hodnoty dostupných sloupců, aby NULL nebyl při filtrování zaměněn za nulu. Mapu lze filtrovat stejně jako tabulku; objekty bez použitelné polohy zůstávají dostupné pouze v projektové tabulce. Skryté sloupce se nenabízejí, zastaralá podmínka je viditelně označena. Nesdílí se neuložené editace hodnot ani zaškrtnuté výběry. Lokální úpravy tabulky se do mapy promítnou až po uložení a načtení dat.

Tyto změny jsou součástí připravované 2.24.1 na GitHubu. Dříve vytvořený aktualizační ZIP je neobsahuje; před nasazením je nutné sestavit a podepsat nový balíček z aktuálního zdroje. Automatické vydávání a instalace z GitHubu zatím nejsou zapnuté.
