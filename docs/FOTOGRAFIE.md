# Fotografie, převod a obnova

Nová instalace: fotografie ploch jsou soubory v private/storage/area_photos, metadata a popisky v svm_area_photos. Fotografie stromů nadále používají map/public/photos a map/public/uploads. Všechny tři složky je nutné zálohovat spolu s databází.

## Převod kopie existující instalace
Tento balíček není aktualizace živého webu. Převod nejprve provádějte na jeho oddělené kopii s odpovídající konfigurací. Před prací zálohujte celou databázi a soubory. Během převodu nepoužívejte editaci ani export.

Přihlaste se jako správce systému a otevřete /maintenance/area_photos.php. Po potvrzení zálohy nástroj doplní file_path a povolí prázdný content. Převádí po pěti fotografiích: zápis souboru, kontrola SHA-256, zápis cesty a uvolnění blobu v jedné databázové transakci pro fotografii. ID, vazby a popisky nemění. Při chybě se aktuální transakce vrátí; předchozí dokončené fotografie zůstávají převedeny. Znovuspuštění pokračuje od zbývajících blobů.

Soubory mají obsahový hash, nesouvisí s interním ID. Shodné fotografie mohou sdílet soubor. Při odstranění záznamu se soubor automaticky nemaže; zamezuje to odstranění sdílené kopie. Nepoužívané soubory mohou zabírat místo a jejich automatický úklid není součástí této verze.

## Záloha a obnova
Zálohujte databázi a všechny složky fotografií ve stejném období bez souběžných úprav. Samotný SQL export už fotografie ploch neobsahuje. Při obnově vraťte odpovídající databázi i soubory; po návratu ke starému kódu musí být obnovena také původní databázová záloha s bloby. Původní zálohu ponechte do ověření fotografií v galerii, kartách a exportu.

Apache musí respektovat private/.htaccess. U jiného serveru zakažte přímý přístup do private odpovídajícím pravidlem. Čtení fotografií ploch zajišťuje API s kontrolou projektu.
