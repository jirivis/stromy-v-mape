# Podmínky před veřejným vydáním

## Bezpečnost
Opravena kontrola přihlášení, projektu, role a CSRF ve starším API tree_measurements/save_tree_measurement. HTTP test pokrývá anonymní přístup, viewer, chybějící token, cizí projekt, povolený zápis a autora. Lokálně prošlo PHP 8.3 / MariaDB 10.11. CI matice 8.3/8.4/8.5 úspěšně prošla pro commit de21b67cc1365b18a01195de1f1f5de3299d8faf (běh 42479880322). Nejde o test všech webových funkcí. Následně doplněna CSRF ochrana mazání stromu a uložení historie; lokálně zatím ověřena syntaxe.

Balíček 2.24.0 vytvořený před touto opravou ji neobsahuje. Není doporučen k novému zákaznickému nasazení. Nevydávat další balíček bez zahrnutí opravy do instalačního i aktualizačního payloadu a ověření podpisu. Celkový bezpečnostní audit tímto není dokončen; zbývají ostatní zápisové endpointy, izolace projektů, přístup k fotografiím, přihlašování a obnova hesel.

## Číselníky
Dodány oba skutečné exporty: 551 taxonů a 1 100 názvů kultivarů. Připraven základ bez původních ID a údajů o hostingu, šest ověřených doplnění POWO, 551 dohledaných identifikátorů původních položek z WCVP v16 a načtení při nové instalaci. SQL doplňuje pouze chybějící názvy a nepřepisuje existující údaje. Podrobnosti a zdroje: database/catalogs/README.md. Integrační test kontroluje instalaci, opakování, zachování ID a místních úprav.

Před veřejnou distribucí zbývá doložit původ a možnost redistribuce původního základu; česká jména a kultivary nejsou ověřené vůči zdrojům. Párování vědeckých názvů má auditní záznam v powo-matches.json; 67 rozdílů autorů zůstává k redakci. Kultivary nemají vazby na druhy. Uživatelský import číselníků s náhledem a verzování jejich aktualizací zatím nejsou hotové. Připravené SQL není automatické sloučení dat na hostingu.

## Aktualizace – konkrétní nedokončené kroky
Podepsaný ručně nahraný balíček a databázová příprava jsou implementované a otestované. Soukromý klíč byl předán vydavateli odděleně. Zdroj je stále soukromý; veřejné release.json a ZIP v GitHub Releases zatím nejsou dostupné. Kontrola aktualizací je proto ve výchozí konfiguraci vypnutá.

Po bezpečnostní kontrole: připravit opravené balíčky, ověřit PHP 8.4, vyřešit licenci, zveřejnit zdroj nebo samostatný veřejný distribuční repozitář, publikovat podepsaný ZIP a release.json, ověřit stažení z hostingu a teprve poté zapnout kontrolu. Každé provedení schvaluje správce instalace. SQL příprava dosud není bezobslužná.

## Kontrola nahrávání fotografií – 9. 11. 2026

Ve starším formuláři tree_save.php opraveno ukládání původní přípony bez ověření obsahu. Všechny soubory se nyní ověří ještě před změnou dat: HTTP upload, MIME a rozměry obrázku, 5 MB / 24 megapixelů, JPG/PNG/WebP. Cílové názvy jsou náhodné a přípona pochází z ověřeného MIME. Nepoužívá se původní přípona ani relativní pracovní adresář. Do photos/uploads přidána pravidla Apache zakazující obsluhu skriptů a konfiguračních souborů; pravidla je ještě nutné ověřit na cílovém Apache (PHP testovací server .htaccess nevyhodnocuje).

HTTP test v izolované databázi ověřuje odmítnutí PHP i falešného PNG, viewer, CSRF a cizí projekt; skutečný PNG pojmenovaný .php musí skončit jako náhodný .png a zůstat čitelný. Testuje se také CSRF/role/projekt u mazání a ukládání historie. Podepsané aktualizace smějí v úložišti měnit pouze dvě konkrétní .htaccess; fotografie, podadresáře a soukromá konfigurace zůstávají blokované. Balíčky zatím nebyly znovu sestaveny ani vydány.

Zůstává otevřeno: soukromí historických přímých odkazů na fotografie (veřejné soubory nejsou chráněny projektovým oprávněním), ověření všech cest veřejného sdílení a zbývající body auditu výše. Samotné ověření obrázku tuto otázku neřeší.

## Kontrola sdílení a změny stavu stromu – 9. 11. 2026

Do označení stromu jako odstraněného, obnovení a správy sdílení doplněna kontrola formulářového tokenu. Mazání, odstranění a obnovení nově znovu ověřují členství a projektovou roli přes auth_api.php; stará role uložená v session po odebrání oprávnění nestačí. Veřejná karta vybírá jen aktivní fotografie, posílá no-store a no-referrer. Nemění se obsah fotografií ani historie.

Lokální HTTP testy s PHP 8.3 / MariaDB prošly: chybějící token, viewer, cizí projekt, bývalý vlastník s rolí owner ve staré session; aktivní veřejná karta zachová aktivní fotografii a nezobrazí archivovanou, zrušený odkaz vrací 404. Pro přímé URL souborů tato změna není řešením přístupových práv; tento bod zůstává otevřený. Nové opravné soubory jsou přidané do seznamu aktualizačního balíčku, samotný balíček dosud nevydán.

## Ochrana přímých URL fotografií – pracovní implementace 9. 11. 2026

Přidán photo.php a přesměrování starých adres photos/uploads přes Apache mod_rewrite. Soubor se vydá jen aktivnímu účtu s aktuálním členstvím/vlastnictvím projektu nebo superadministrátorovi; veřejný token dovoluje pouze aktivní fotografii konkrétního stromu a ověřuje se jeho expirace i zrušení. Sdílená karta používá odkazy s ID fotografie a tokenem. Archiv přístupný oprávněnému členovi projektu zůstává zachován. Vazby v databázi, názvy a samotné soubory se nepřepisují. Neznámé soubory bez vazby na strom nejsou veřejně přístupné.

Lokální HTTP testy PHP gateway prošly: anonymní přístup, cizí projekt, deaktivovaný účet, člen projektu, platný/neplatný rozsah tokenu, archiv, expirace, odvolání sdílení a traversal. Testovací PHP server pravidla Apache NEVYHODNOCUJE. Před vydáním je nutný integrační test na Apache s AllowOverride a mod_rewrite, včetně starých přímých odkazů. Bez mod_rewrite pravidla přístup blokují; bez povoleného AllowOverride by se ochrana neuplatnila. Vydání proto zůstává blokované až do ověření skutečné konfigurace. Je potřeba zahrnout též omezení PHP session cache oproti mapovým náhledům a ověřit karty/export/veřejné sdílení na hostingu.

### Výsledek Apache / PHP matice

Commit aee35144ce031f342fdf2fdecd93aa830f02c9d7 úspěšně prošel GitHub Actions během 42685078283 na PHP 8.3, 8.4 i 8.5. Zahrnuje skutečný Apache s .htaccess, mod_rewrite a proxy na PHP: původní URL vyžaduje autorizaci, zachovává cookie i token, odmítá archiv a cizí projekt. Tím je výše uvedený požadavek integračního testu Apache splněn pro testovací konfiguraci. Ověření AllowOverride/mod_rewrite konkrétního hostingu a celková kontrola instalace před vydáním nadále zbývají. Živý web nebyl změněn.

## Přihlášení a obnova hesla – 9. 11. 2026

Doplněn formulářový token pro login, žádost o obnovu i samotné nastavení hesla, no-store/no-referrer a cookies HttpOnly/SameSite=Lax/Secure pro konfigurované HTTPS. Přihlášení regeneruje ID session a odstraňuje staré formulářové tokeny. Session obsahuje otisk databázového hashe hesla; při ověřování identity je porovnán s aktuální hodnotou. Změna hesla proto zneplatní předchozí přihlášení při dalším chráněném požadavku. Databázové heslo se z načteného účtu nevrací. Po prvním nasazení je nutné jednorázové opětovné přihlášení všech uživatelů (staré session nemají otisk). Není potřeba změna SQL schématu.

Reset zachovává zamykání tokenu, kontrolu expirace a jednorázovost. Doplněna horní hranice 72 bajtů a zákaz NUL, aby nedocházelo ke zkrácení bcrypt hesla či výjimce z neplatného vstupu. Původní dolní hranice 10 znaků zůstává. HTTP test bez odesílání pošty ověřuje CSRF, cookies, rotaci session, reset, délku, opakované použití, expiraci a odmítnutí starého přihlášení. Lokálně PHP 8.3 prošlo.

Audit přihlášení není ještě úplný: zbývá omezení opakovaných pokusů a žádostí o obnovu (s ohledem na skutečné IP za hostingovou proxy), kontrola ostatních administračních zápisů a integrační ověření doručování pošty na hostingu.

## Omezení přihlašování a resetů – 9. 11. 2026

Počítadla v private/auth_limits.sqlite3 přežívají nové cookies/session a nepotřebují ALTER v MySQL. Přihlášení: 8 požadavků na účet + REMOTE_ADDR během 60 sekund, celkem 20 na účet za 15 minut (započítávají se i úspěšná přihlášení). Obnova: 3 požadavky na účet/e-mail za hodinu. U existujícího účtu se používá databázové ID, takže varianty názvu či e-mailu podle databázové collation limit neobcházejí. Neexistující účty mají stejná pravidla a běžná odpověď obnovy neprozrazuje existenci účtu. X-Forwarded-For se nedůvěřuje. Sdílená proxy proto sdílí pouze limit u stejného účtu, ostatní účty nejsou omezeny společným IP počítadlem.

Zápis je atomický (SQLite BEGIN IMMEDIATE), klíče HMAC místo prostých jmen/e-mailů/IP, expirované záznamy se mažou, nejvýše 10 000 současných záznamů. Při limitu HTTP 429 s Retry-After; při nedostupném úložišti 503 místo obejití kontroly. Cílené vyčerpání limitu může dočasně omezit přihlašování konkrétního účtu; aktivní relace zůstávají funkční. Nejde o ochranu proti všem distribuovaným útokům či DDoS.

Testy ověřují per-account/per-client limity, jiný účet na stejné IP, změnu IP, expiraci, obnovení kvóty, opakované HTTP požadavky z nových session a Retry-After. Test obnovy používá neexistující adresu a neposílá e-maily. SQLite3 již patří mezi instalační požadavky aplikace. Živý hosting zatím nezměněn.
