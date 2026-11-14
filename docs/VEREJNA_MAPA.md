# Veřejná mapa a podněty obyvatel

## Zapnutí

1. Správce projektu otevře z dashboardu **Veřejná mapa a podněty**.
2. Vybere zveřejněná pole zvlášť pro stromy a plochy. Fotografie, jejich popisky, formulář podnětů a e-mailová upozornění jsou samostatné volby.
3. Zapne mapu a uloží nastavení. Zkopíruje její odkaz.
4. V tabulce projektu zaškrtne **Veřejné** u konkrétních objektů nebo označí více řádků a použije **Zveřejnit vybrané**.

Nová instalace i aktualizace ponechává vše neveřejné. Starší pole projects.visibility se nepoužije k automatickému zveřejnění. Údaj „Veřejné“ je samostatný od běžného výběru řádků a filtrů. Změny zveřejnění mohou provádět jen vlastník a administrátoři; editor ani čtenář nemají oprávnění. Objekt bez polohy se na mapě nezobrazí.

Veřejná mapa ukazuje evidenční čísla, polohu a pouze výslovně zvolená pole z pevného seznamu. Interní ID, uživatelské účty, historie a interní poznámky se neposílají do prohlížeče. Veřejná poznámka stromu note_public je volitelná. Každé povolení fotografií zveřejní aktivní fotografie označeného objektu, včetně obsahu souboru; před zapnutím zkontrolujte vhodnost snímků a jejich popisků. Volba popisků je oddělená.

Odškrtnutí objektu nebo vypnutí mapy zablokuje další načtení detailů i fotografií. Již stažené nebo jinak zachycené veřejné údaje samozřejmě nelze vzít zpět. Projekty, objekty i fotografie se na každém anonymním požadavku znovu ověřují; veřejné identifikátory jsou náhodné a nejsou interní databázová ID.

## Podněty

Formulář přijímá text 10–2000 znaků a nepovinný kontaktní e-mail. Podněty se nepublikují a nepřepisují objekt ani note_public. Správci vidí schránku podnětů, stavy Nový / Řeší se / Vyřešený a mohou záznam včetně kontaktu trvale smazat. Dashboard ukazuje počet nových podnětů aktivního projektu. Kontakt uchovávejte jen po dobu potřebnou k vyřízení.

Ochrana: formulářový token vázaný na relaci a objekt, minimální doba vyplnění, honeypot, limit 5 podnětů za hodinu na zdrojovou IP a 50 za hodinu na projekt. Limity nepřebírají podvržené forwarded hlavičky; za reverzní proxy se limit může sdílet. Neukládá se surová IP, pouze krátkodobý HMAC klíč počítadla. Toto je základní ochrana proti spamu, nikoli záruka proti distribuovanému útoku.

Volitelný e-mail je upozornění na novou aktivitu, nejvýše jednou za hodinu pro projekt; není zprávou ke každému podnětu. Posílá se aktivnímu vlastníkovi a administrátorům s e-mailem (nejvýše 20 adres). Neobsahuje text podnětu ani kontakt oznamovatele. Používá poštu instalace; při nedostupné poště podnět zůstává ve schránce. Skutečné doručení je třeba ověřit na konkrétní instalaci.

## Instalace a databáze

Databázová příprava 08_public_map.sql přidává čtyři samostatné tabulky (nastavení, zveřejněné objekty, podněty, krátkodobé limity). Nemění trees, fotografie ani historii a nic nezveřejňuje. Opakované spuštění existující nastavení nepřepíše. Na hostingu s omezenými databázovými právy je potřeba SQL přípravu provést účtem správce databáze; běžné přihlášení aplikace administrátorská databázová práva nedostává.

Testy: anonymní mapa bez přihlášení, dvojí zapnutí, soukromé údaje a interní ID, oprávnění a CSRF, cizí projekty, odvolání přístupu k fotografii, neveřejné podněty a kontakty, stavy, mazání, opakování odeslání a limitování. Produkční data se při testech nepoužívají.

## Veřejné prohlížení – rozšíření po 2.25.0

Veřejná mapa nabízí OSM, ortofoto ČÚZK, bílý podklad a překryv katastru. Pro OSM používá standardní adresu tile.openstreetmap.org a politiku strict-origin-when-cross-origin: poskytovatel obdrží jen původ webu, nikoli token projektu. Při chybě dlaždic je nabídnuto přepnutí podkladu; trvalé blokování poskytovatelem tím nelze zaručeně odstranit.

Návštěvník může zvolit značku stromu (kruh, čtverec, trojúhelník, strom), čísla objektů a kategoriální barvení dle zveřejněného údaje s legendou. Devět barev se u většího počtu kategorií opakuje. Vyhledávání v číslech a veřejných hodnotách, přepínače geometrie a jedna atributová podmínka společně filtrují mapu i tabulku. Číselné porovnání podporuje desetinnou čárku. Tabulka je pouze pro čtení, řaditelná, stránkovaná po 50 objektech; kliknutí na číslo otevře detail a přiblíží objekt. Zatím obsahuje stejně jako mapa jen objekty s použitelnou geometrií.

Mapová odpověď obsahuje jen výslovně publikované objekty a hodnoty z téhož povoleného seznamu jako karta. Filtry ani symbolizace neposkytují skryté hodnoty. Tabulka nevolá přihlašované editační API. Nastavení zobrazení návštěvníka nemění databázi.
