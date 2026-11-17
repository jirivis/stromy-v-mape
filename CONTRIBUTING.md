# Jak přispět

Děkujeme za opravy, testování i odborné připomínky. Aplikace se aktivně vyvíjí; popsané testy nejsou úplným bezpečnostním auditem.

## Chyby a návrhy

Pro běžné chyby a návrhy použijte Issues. Uveďte verzi aplikace, prohlížeč, PHP, postup reprodukce, očekávaný a skutečný výsledek. Přiložte jen anonymizovaný minimální příklad. Nikdy nepřikládejte config.php, hesla, tokeny, neveřejné fotografie, kontakty nebo databázi klienta. Bezpečnostní nálezy řešte podle SECURITY.md, nikoli ve veřejném issue.

## Pull request

1. Vytvořte pracovní větev z aktuální vývojové větve. Do dokončení sjednocení větví je to release/2.24.0; výchozí main zatím nepovažujte za aktuální instalační zdroj. Externí fork/PR vyžaduje veřejný repozitář nebo přidělený přístup.
2. Omezte PR na jednu srozumitelnou změnu. Popište problém, výsledné chování a způsob ověření.
3. Zachovejte serverovou kontrolu projektových oprávnění a CSRF. Interní ID nesmějí být zveřejněná jako uživatelská označení.
4. Databázové změny musí mít migrační postup i aktualizované schéma nové instalace; nesmějí přepsat místní data. Přidejte příslušné testy.
5. Vlastní kód přispíváte pod GPL-3.0-or-later. Přidávejte pouze obsah, k jehož poskytnutí máte oprávnění. Cizí knihovny a taxonomická data musí mít doložený zdroj a kompatibilní podmínky.

## Ověření

GitHub Actions testuje PHP 8.3/8.4/8.5 a MariaDB. Přesné závislosti a příkazy jsou v .github/workflows/php-checks.yml. Integrace používá dočasné databáze a syntetická data; nikdy ji nespouštějte nad produkční databází. Nová instalace se testuje z ZIPu sestaveného release-tools/package_installer.py. Podpisový soukromý klíč vydavatele není potřeba pro testy a nesmí být součástí PR.

## Taxonomie

Rozlišujte původní znění, zdrojový taxon, přijatý taxon a synonymum. Opravy autorů a jmen musí mít zdroj a náhled změn; nepřepisujte automaticky existující stromy. Kultivar je volitelný a nelze jej doplnit pouze z názvu druhu. Viz database/catalogs/PROVENANCE.md.
