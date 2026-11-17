# Stromy v mapě 2.25.4 – nová instalace

Kompletní balíček pro samostatnou doménu nebo subdoménu a **novou prázdnou databázi**. Nejde o aktualizační ZIP: nenahrávejte jej přes existující aplikaci a nenahrávejte jej do jejího aktualizátoru. Prezentační weby ani zákaznická data nejsou součástí.

## Požadavky

- Apache 2.4, účinné `.htaccess` a mod_rewrite; HTTPS.
- PHP 8.3 nebo novější; automatické testy běží na 8.3, 8.4 a 8.5.
- mysqli/mysqlnd, mbstring, fileinfo, GD, ZIP, SQLite3, DOM, OpenSSL. EXIF doporučeno pro datum pořízení fotografií.
- MariaDB; testovací prostředí používá MariaDB 10.11. Účet instalace musí mít právo vytvořit tabulky. Samotnou databázi zakládá správce v administraci hostingu.
- PHP musí zapisovat do private (včetně podsložek), map/public/photos a map/public/uploads. Aktualizátor potřebuje také zápis do aktualizovaných aplikačních souborů. Nepoužívejte plošně oprávnění 777.
- Pro mapové podklady a externí knihovny je potřeba internet; server potřebuje odchozí HTTPS ke GitHubu pro aktualizace. SMTP je volitelné, ale nutné pro odesílání e-mailů.

## Postup

1. Založte samostatnou doménu/subdoménu s HTTPS a novou prázdnou databázi. Instalace do podadresáře není podporovaná.
2. Rozbalte ZIP na počítači. Obsah **application/** nahrajte do webového kořene, včetně skrytých souborů `.htaccess`. Vedle sebe musí být dashboard.php, app, private, install a map. Složku application samu do URL nevkládejte.
3. Zkopírujte `private/config.example.php` na `private/config.php`. Vyplňte URL bez podadresáře, databázový server, název, uživatele a heslo; případně SMTP. Žádné skutečné přístupy nejsou v balíčku.
4. Do `installation_key` vložte vlastní náhodný jednorázový klíč alespoň 32 znaků. Lze jej vytvořit správcem hesel nebo příkazem `php -r "echo bin2hex(random_bytes(32));"`. Klíč ani konfiguraci nikam veřejně neposílejte.
5. Otevřete `https://vase-domena/install/`. Po splnění požadavků zadejte klíč, jméno, e-mail a heslo prvního správce. Instalátor vytvoří tabulky, číselníky a správce. Existující nebo částečně naplněnou databázi odmítne; nic nemaže.
6. Po úspěchu vymažte instalační klíč z konfigurace a odstraňte složku install. Ponechte `private/installed.lock`. Přihlaste se a založte první projekt.
7. Ověřte strom i plochu, fotografii, import/export a práva dalšího uživatele. Soukromá data nesmějí být dostupná přes URL `/private/` ani `/database/`; ověřte také, že fotografie soukromého projektu nelze zobrazit bez oprávnění. Pokud server pravidla ignoruje, instalaci nepoužívejte, dokud hosting ochrany neopraví.
8. V Aktualizacích zkontrolujte vydání. Zdroj `jirivis/stromy-v-mape-updates`, veřejný podpisový klíč a kontrola na dashboardu jsou již ve vzoru nastavené. Instalaci každé aktualizace schvaluje správce. Před ní zálohujte databázi, konfiguraci a fotografie.

## Číselníky a veřejná distribuce

Instalátor obsahuje 557 taxonů a 1 099 názvů kultivarů. Kultivar se automaticky nepřiřazuje. Mapování POWO/WCVP a jeho původ popisuje `application/database/catalogs/README.md`; nejde o živou synchronizaci s POWO. Původ původního seznamu českých názvů a kultivarů není doložen a licence celé aplikace dosud nebyla zvolena. Tento balíček je připraven k ověření vlastní instalace; před veřejným nabízením úplné distribuce je potřeba tyto otázky uzavřít. Licence přibalených komponent zůstávají v jejich složkách.

## Aktualizace a obnova

Balíček obsahuje verzi 2.25.4. Aktualizátor nabízí balíček podle nainstalované výchozí verze; při větším odstupu se postupuje po kompatibilních krocích. Databázové změny mohou vyžadovat samostatnou SQL přípravu správcem. Automatická kontrola neznamená automatickou instalaci bez souhlasu.

Instalátor není určen pro obnovu produkčních dat. Postup zálohy a obnovy je v `docs/OBNOVA_A_OPRAVNENI.md`, fotografie v `docs/FOTOGRAFIE.md`. Při chybě instalace mohou zůstat některé tabulky; neopakujte ji nad důležitými daty. Použijte novou prázdnou databázi a původ chyby zjistěte z PHP logu.

## Rozsah ověření

Automatický test sestavuje tento ZIP, rozbalí jej a provádí webovou instalaci do prázdné databáze, kontroluje číselníky, uzamčení instalátoru, odmítnutí chybného klíče a neprázdné databáze, přihlášení správce a obnovu syntetické zálohy. Testy nejsou úplným bezpečnostním auditem ani ověřením konfigurace konkrétního hostingu. Na čistém WEDOS hostingu je ještě potřeba ověřit funkčnost, pravidla Apache, poštu a odchozí HTTPS.
