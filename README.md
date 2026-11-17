# Stromy v mapě – aplikace 2.25.4

Aplikace pro evidenci stromů a porostních ploch, fotografie, historii, import/export a volitelnou veřejnou mapu. Prezentační weby ani zákaznická data nejsou součástí.

## Nová instalace

Postup a požadavky: [docs/INSTALACE.md](docs/INSTALACE.md). Kompletní balíček se sestavuje příkazem:

```sh
python3 release-tools/package_installer.py --output /cesta/install-2.25.4.zip
```

ZIP obsahuje application/ a návod. Používá explicitní seznam souborů, nepřibaluje místní konfiguraci, fotografie, testy, podpisové soukromé klíče ani vývojové nástroje. Test čisté instalace rozbaluje tento ZIP, nikoli vývojový checkout. Vlastní kód je GPL-3.0-or-later (LICENSE a LICENSING.md). Před veřejnou distribucí zbývá doložit původ původních českých názvů a kultivarů; podrobnosti jsou v instalačním návodu a database/catalogs/README.md.

## Aktualizace

Veřejný distribuční zdroj je jirivis/stromy-v-mape-updates. Nové instalace mají ve vzoru konfigurace tento zdroj, veřejný podpisový klíč a kontrolu vydání na dashboardu. Každou instalaci aktualizace schvaluje správce. Instalační ZIP není aktualizační balíček. SQL změny mohou vyžadovat přípravu správcem databáze.

Verze 2.25.4 zpřesňuje projektová oprávnění a chrání adresář kontaktů. Oprávnění a obnova: [docs/OBNOVA_A_OPRAVNENI.md](docs/OBNOVA_A_OPRAVNENI.md). Fotografie: [docs/FOTOGRAFIE.md](docs/FOTOGRAFIE.md).

Čistou instalaci a regresní testy ověřuje CI na PHP 8.3/8.4/8.5 s MariaDB. Cílový hosting, doručování pošty a obnovu reálných záloh je potřeba ověřit samostatně. Nejde o potvrzení úplného bezpečnostního auditu. Modul ocenění AOPK zatím není implementován; databázová příprava není kalkulačka.

## Příspěvky a licence

Vlastní kód: **GPL-3.0-or-later**, viz LICENSE a LICENSING.md. Cizí komponenty: THIRD_PARTY_NOTICES.md. Příspěvky a testy: CONTRIBUTING.md. Bezpečnostní hlášení: SECURITY.md. Taxonomický původ: database/catalogs/PROVENANCE.md.

Issues jsou zapnuté a pull requesty podporované. Zdrojový repozitář je zatím soukromý; veřejnost nemá přístup, dokud správce nerozhodne o zveřejnění. Aktuální vývoj je ve větvi release/2.24.0; šablony příspěvků se běžně nabídnou až po začlenění do výchozí větve.
