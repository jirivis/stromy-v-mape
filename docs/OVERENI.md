# Ověření V2.22 — 7. 11. 2026

Lokálně: PHP 8.3, MariaDB 10.11.14, skutečná izolovaná databáze se syntetickými daty.

Prošlo:
- Instalace 20 aplikačních tabulek a evidence verzí do prázdné databáze; vytvoření správce a ověření hashovaného hesla.
- Odmítnutí opakované instalace do neprázdné databáze.
- Uložení stáří 1000 let, celočíselných rozměrů a nezměněného interního ID.
- Převod fotografie, shoda bajtů, zachování ID, vazby a českého popisku; opakování bez další změny.
- Doplnění sloupce a změna nullability ve starém schématu, následný převod dvou fotografií.
- Skutečný migrační endpoint, odmítnutí neplatného CSRF tokenu.
- Čtení fotografie přes API ploch a tiskový endpoint karty.
- Kompletní export XLSX, GPKG, QGZ a fotografií; galerie stromu podle usr_id 2, nikoli interního ID 700.
- Odmítnutí neplatné cesty fotografie, čtení původního blobu při chybějícím souboru.
- Odmítnutí deaktivovaného účtu.
- Nativní PHP lint všech 191 PHP souborů, syntaxe nového JavaScriptu.

Test instalace odhalil překročenou velikost řádku po přechodu na utf8mb4. Dlouhé poznámky v novém schématu jsou proto TEXT. Nativní lint odhalil nesprávné pořadí strict_types a bootstrapu v pěti starších stránkách; opraveno.

Neověřeno v tomto prostředí: konfigurace Apache/WEDOS, skutečné SMTP doručení, vzhled formulářů v desktopovém QGIS, kompletní všechny uživatelské scénáře a úplný bezpečnostní audit. Automatické testy nenahrazují závěrečnou kontrolu cílové instalace.


# Ověření aktualizátoru V2.23.0

Provedeno na izolované MariaDB 10.11.14 / PHP 8.3 se syntetickými daty a dočasnými testovacími podpisovými klíči. Klíče ani testovací data nejsou v distribučním balíčku.

- Ověření podpisu a souborových hashů; odmítnutí pozměněného obsahu a nesouhlasící výchozí verze.
- Kontrola zakázaných cest konfigurace, fotografií a přechodu do nadřazených složek.
- Výhradní aktualizace: aktivní požadavky blokují její spuštění.
- Úspěšná změna verze, doplnění databázového sloupce, evidence migrace, zachování původních ID, evidenčních čísel, poznámek, konfigurace a fotografie.
- Záměrná chyba SQL ponechá údržbu aktivní a neposune číslo verze.
- Skutečný HTTP formulář: zákaz běžnému uživateli, kontrola CSRF, nahrání bez instalace, požadavek obou potvrzení, úspěšná schválená instalace.
- Nová instalace verze 2.23.0: vytvoření všech tabulek a správce.
- Nativní lint 195 PHP souborů a syntaxe JavaScriptu dashboardu.

Zůstává neověřené skutečné stažení z budoucího GitHub repozitáře, WEDOS/Apache oprávnění a limity konkrétního hostingu. Nejde o potvrzení úplného bezpečnostního auditu celé původní aplikace.
