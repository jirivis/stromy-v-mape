# Stav přípravy vydání – 9. 11. 2026

Pracovní větev release/2.24.0, PR #3. Živý web nebyl změněn. Dříve předaný ZIP 2.24.0 neobsahuje všechny bezpečnostní opravy a nesmí být použit jako aktuální instalační balíček.

## Implementované a testované ochrany

- Starší API měření: přihlášení, aktuální role, izolace projektů, CSRF a správný autor zápisu.
- Ukládání, mazání, označení odstranění a obnovení stromu, historie a sdílení: formulářové tokeny; změny rolí se ověřují proti databázi.
- Nahrávání stromových fotografií: ověření skutečného typu a rozměrů před zápisem dat, 5 MB / 24 MP, náhodný název s odvozenou příponou. Pravidla Apache brání obsluze skriptů v úložišti.
- Přímé staré URL photos/uploads: přes photo.php, členství v projektu nebo platný token pro aktivní fotografii konkrétního stromu. Archiv veřejným tokenem nepřístupný. Soukromá konfigurace a fotografie zůstávají chráněné i před přepsáním aktualizačním balíčkem; výjimkou jsou dvě přesně určené ochranné .htaccess.
- Přihlášení/obnova: CSRF, no-store/no-referrer, cookie flags, regenerace session, zneplatnění přihlášení po změně hesla. Po nasazení jednorázové nové přihlášení všech uživatelů. Resetovací token zůstává jednorázový se zamčením a opětovnou kontrolou expirace.
- Limity v private/auth_limits.sqlite3: 8 přihlášení na účet + REMOTE_ADDR za 60 sekund; 20 na účet za 15 minut (včetně úspěšných); 3 žádosti o obnovu za hodinu. Nepřebírá se X-Forwarded-For. Ostatní účty za stejnou proxy mají samostatné limity. HTTP 429 + Retry-After, při nedostupném úložišti 503. Počítadla jsou atomická, expirují, nejvýše 10 000 záznamů, HMAC klíče. Cílené vyčerpání kvóty může dočasně omezit konkrétní účet; nejde o úplnou ochranu proti DDoS.
- Administrace uživatelů/projektů/zákazníků a přepnutí projektu: doplněny formulářové tokeny; role a projektová oprávnění zůstávají vynucené serverem. Pozvánky: CSRF, limit délky hesla, zachované zamykání, expirace a jednorázovost.
- Deaktivace účtu: ve stejné transakci se zruší dosud nepoužité pozvánky i resetovací odkazy, aby stará pozvánka účet znovu neaktivovala.

HTTP testy administrace ověřují odmítnutí podvrženého POST i pro superadministrátora, odmítnutí viewer/cizího projektu, úspěšnou úpravu vlastního projektu a přidělení role, nemožnost vlastníka převést vlastnictví podvrženým parametrem, jednorázovost pozvánky a zrušení odkazů při deaktivaci. Testy neposílají e-maily.

Lokální testy používají PHP 8.3 / MariaDB 10.11. Apache pravidla a předchozí ochrany fotografií prošly na PHP 8.3/8.4/8.5 v CI běhu 42685078283 pro commit aee35144ce031f342fdf2fdecd93aa830f02c9d7. Nové změny se ověřují v matici při každém push. Jednotlivé úspěšné testy nejsou úplným bezpečnostním auditem celé aplikace.

## Číselníky

Základ: 551 původních taxonů, 1 100 názvů kultivarů; šest doplněných druhů. 551 původních taxonů má mapování na WCVP v16 s auditním JSON, statusy a odkazy. 67 rozdílů autorů zůstává k redakci, původní data se nepřepisují. Kultivar je nepovinný, bez automatického přiřazení. Instalace doplní názvy bez přebírání cizích interních ID; opakování zachová místní úpravy. Viz database/catalogs/README.md včetně kolize Krejčí/Krejci.

Zbývá doložit původ/redistribuci původních českých názvů a kultivarů, vybrat licenci aplikace a dokončit správu aktualizací číselníků s náhledem a souhlasem správce. Průběžná synchronizace s živým POWO není hotová.

## Co zbývá před vydáním

1. Dokončit kontrolu dalších cest importu/exportu, plošných objektů a souvisejících administračních akcí. Ověřit celé uživatelské scénáře, nejen izolované endpointy.
2. Ověřit na cílovém hostingu AllowOverride/mod_rewrite, přístup k souborům, doručování pošty a obnovu ze zálohy. Testy na vlastním Apache nejsou ověřením konfigurace WEDOS.
3. Sestavit nové instalační a podepsané aktualizační balíčky, ověřit aktualizaci skutečné 2.23.0 a zachování konfigurace, ID, historie a fotografií. Původní instalace potřebuje bootstrap aktualizátoru. Změny MySQL schématu stále vyžadují ruční SQL přípravu; webový účet neprovádí DDL.
4. Vyřešit licenci, zveřejnit distribuční zdroj, podepsaný ZIP a release.json v GitHub Releases; ověřit stažení z hostingu. Repo je nyní soukromé a automatická kontrola nových verzí není aktivní. Každé provedení aktualizace schvaluje správce.
