# Verze 2.24.0 – připraveno k ověření

Stejný kód pro čistou instalaci a přechod z 2.23.0. Nové sloupce ocenění jsou pouze příprava, kalkulačka a její UI nejsou zapnuté.

Ověřeno: MariaDB 10.11, PHP 8.3, čisté schéma, opakovatelná migrace, podepsaný balíček, blokace před SQL přípravou, instalace omezeným účtem, zachování ID, konfigurace a testovací fotografie. Automatické integrační testy prošly také na PHP 8.4 a 8.5 v GitHub Actions. Konfigurace cílového hostingu dosud nebyla ověřena. Kompletní bezpečnostní audit a licenční příprava k veřejnému zveřejnění nejsou dokončené.

První přechod vyžaduje čtyři soubory z bootstrapu (app/update.php, app/update_database.php, app/update-publisher.pem, updates/index.php), nastavení veřejného klíče v private/config.php a SQL přípravu přes správce databáze. Žádný krok nepřepisuje celou produkční aplikaci instalačním ZIPem. Podrobný postup je u distribuovaných balíčků.

Soukromý podpisový klíč je uložen odděleně u vydavatele; nikdy nepatří do repozitáře ani na hosting.

## Kontrola distribučních balíčků

`tests/release_package.py` sestaví instalační ZIP, aktualizační ZIP a bootstrap s dočasným testovacím klíčem. Kontroluje podpis, hashe, shodu souborů instalace a aktualizace, přítomnost bezpečnostních oprav a číselníků a nepřítomnost soukromého klíče a produkční konfigurace. V databázové sadě navíc provede skutečné `upApply` sestaveného balíčku a porovná data před a po, zachování konfigurace a fotografie, zálohu původního souboru a ukončení údržby. Jde o syntetickou instalaci označenou 2.23.0, nikoli kopii produkčního webu.
