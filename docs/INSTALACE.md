# Nová testovací instalace V2.23.0

Tento balíček instalujte na samostatnou testovací doménu/subdoménu s novou prázdnou databází. Není aktualizačním balíčkem pro stávající stromyvmape.cz. Nevkládejte do něj konfiguraci produkční databáze.

1. Připravte PHP 8.2+ a rozšíření mysqli/mysqlnd, mbstring, GD, fileinfo, ZipArchive, SQLite3, DOM a OpenSSL. Všechny požadavky kontroluje instalační stránka. Povolte HTTPS. Balíček předpokládá Apache 2.4 s účinnými pravidly .htaccess.
2. U hostingu vytvořte novou prázdnou MariaDB databázi a uživatele s právy pro vytvoření tabulek a běžnou práci aplikace. Instalátor nevytváří samotnou databázi ani hostingový účet.
3. Nahrajte obsah application do kořene samostatné domény. Podadresářová instalace zatím podporována není.
4. Zkopírujte private/config.example.php na private/config.php a vyplňte app.url, databázi a případně SMTP. app.url je např. https://test.example.cz bez koncového lomítka a bez podadresáře.
5. Do installation_key zadejte jednorázový náhodný klíč alespoň 32 znaků. Vygenerujte jej správcem hesel, případně lokálním příkazem PHP: php -r "echo bin2hex(random_bytes(32));". Klíč ani config.php nepatří do GitHubu.
6. Otevřete https://vase-domena/install/. Vyřešte chybějící požadavky, zadejte instalační klíč a údaje prvního správce. Instalátor odmítne každou neprázdnou databázi a vyžaduje HTTPS (výjimka lokální loopback).
7. Po dokončení vymažte installation_key, odstraňte složku install a přihlaste se. Vytvořte projekt a několik testovacích stromů/ploch. Číselníky druhů a kultivarů zatím nemají předvyplněný obsah.
8. Ověřte přidání, editaci, historii, nahrání fotografie, import, export a chování uživatelů s různými rolemi. Ověřte, že URL /private/config.php a /database/schema.sql nelze stáhnout.

## Pokud instalace selže
Instalátor nikdy automaticky nemaže tabulky. DDL operace MariaDB mohou zanechat částečně vytvořené schéma. Pro opakovaný test použijte jinou novou prázdnou databázi nebo ručně odstraňte výhradně tabulky neúspěšné testovací instalace po ověření, že neobsahují potřebná data. Podrobnosti chyby jsou v serverovém PHP logu. Jestli byl správce již vytvořen, ale selhal zápis zámku, instalaci neopakujte; vymažte klíč a složku install a ověřte přihlášení.

## Co tato etapa mění
- Instalátor pro prázdnou databázi a prvního správce; souběžné spuštění chrání souborový zámek. Neprázdná databáze tvoří druhou ochranu proti opakování.
- Eviduje se baseline-2.23.0 v svm_schema_migrations. Aktualizátor pro navazující podepsané balíčky je popsán v AKTUALIZACE.md.
- Chráněné stránky i API znovu čtou aktivitu a systémové role uživatele z databáze. Kontroly přístupu k projektu v API zůstávají zachovány.
- Nová databáze má unikátní username; staré databázi se index automaticky nepřidává.

## Co ještě není dokončené
Úplný audit všech zápisových endpointů a CSRF, sjednocení typů cizích klíčů, hotová licence veřejného release a distribuovatelná data číselníků. Jde o testovací sestavení, ne o hotovou veřejnou distribuci nebo potvrzení bezpečnosti celé aplikace.

## Fotografie a převod
Nové fotografie ploch se ukládají do private/storage/area_photos. Databáze obsahuje cestu, popisek a vazby. Postup převodu existujících fotografií a záloh je v FOTOGRAFIE.md.

## Ověření
Lokální integrace byla provedena na PHP 8.3 a MariaDB 10.11.14. Podrobnosti a hranice ověření: OVERENI.md.
