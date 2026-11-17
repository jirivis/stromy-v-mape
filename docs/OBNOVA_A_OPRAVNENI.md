# Oddělení kontaktů a obnova instalace

## Oprávnění

- Superadministrátor spravuje celkový adresář uživatelů a zákazníků a převádí vlastnictví projektů.
- Vlastník a projektový administrátor spravují přístupy svého projektu. Přidávají existující aktivní účet přesným e-mailem; neprocházejí adresář celé instalace. Změna role a odebrání přístupu platí od následujícího požadavku.
- Obecná role admin dovoluje založit vlastní projekt, ale sama nezpřístupňuje cizí projekty nebo adresář kontaktů. Nový vlastník je vynucen serverem, nikoli pouze formulářem.
- Výběr zadavatele nabízí pouze jméno a IČO zákazníků projektů, jejichž je uživatel vlastníkem nebo administrátorem. Stejné omezení platí při uložení. Nového zákazníka zakládá superadministrátor.
- Editor a prohlížející nemění členství ani správu projektu.

## Záloha a obnova na hostingu

1. Přerušte zápisy všech uživatelů, importy a úpravy fotografií. Zálohujte databázi a soubory ve stejném časovém bodě; samotný ZIP aktualizace není záloha dat.
2. Uchovejte kompletní SQL export a odpovídající verzi aplikace včetně private/config.php, private/installed.lock, private/storage, map/public/photos a map/public/uploads. Zachovejte ochranné .htaccess. Záloha obsahuje hesla a osobní data: uložte ji mimo veřejný web, s omezeným přístupem. Nepřidávejte ji do GitHubu.
3. Obnovu nejprve zkoušejte v oddělené instalaci a samostatné databázi. Omezte přístup k této kopii a vypněte odesílání pošty. Ověřte, že její konfigurace neukazuje do produkční databáze. V nové prázdné databázi importujte SQL pod účtem s potřebnými právy; nespouštějte nad obnovenými daty instalátor.
4. Obnovte soubory ze stejné zálohy a upravte v kopii pouze adresu instalace a databázové připojení. Běžnému webovému účtu nepřidělujte administrátorská databázová práva. Ověřte zápis do úložišť a ochranu soukromých cest.
5. Ověřte přihlášení, verzi, počty projektů a objektů, interní a uživatelská ID, historii, fotografie a jejich popisky, export i veřejnou mapu. Ověřte také odmítnutí cizích a nepřihlášených účtů. Pro opětovné spuštění provozu musí být databáze a soubory konzistentní.

Automatizovaný test tests/install_restore_http.py používá výhradně dočasnou databázi a syntetická data: webový instalátor, nesprávný klíč, uzamčení instalace, zákaz opakované instalace do neprázdné databáze, číselníky a neveřejné výchozí nastavení. Poté porovnává SQL export před/po obnově, konfigurační soubor, verzi a zkušební soubory fotografií a ověří přihlášení obnoveného správce. Nenahrazuje zkoušku skutečné zálohy na WEDOS; neověřuje doručení e-mailů ani nastavení Apache cílového hostingu.

Opravy oprávnění jsou součástí vydání 2.25.4, včetně app/project_directory.php. Instalační balíček obsahuje stejnou verzi; zveřejněný aktualizační ZIP je určen pouze pro přechod z 2.25.3.
