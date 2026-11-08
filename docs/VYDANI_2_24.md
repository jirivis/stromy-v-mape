# Verze 2.24.0 – připraveno k ověření

Stejný kód pro čistou instalaci a přechod z 2.23.0. Nové sloupce ocenění jsou pouze příprava, kalkulačka a její UI nejsou zapnuté.

Ověřeno: MariaDB 10.11, PHP 8.3, čisté schéma, opakovatelná migrace, podepsaný balíček, blokace před SQL přípravou, instalace omezeným účtem, zachování ID, konfigurace a testovací fotografie. Na skutečném PHP 8.4 a hostingu dosud neověřeno. Kompletní bezpečnostní audit a licenční příprava k veřejnému zveřejnění nejsou dokončené.

První přechod vyžaduje čtyři soubory z bootstrapu (app/update.php, app/update_database.php, app/update-publisher.pem, updates/index.php), nastavení veřejného klíče v private/config.php a SQL přípravu přes správce databáze. Žádný krok nepřepisuje celou produkční aplikaci instalačním ZIPem. Podrobný postup je u distribuovaných balíčků.

Soukromý podpisový klíč je uložen odděleně u vydavatele; nikdy nepatří do repozitáře ani na hosting.
