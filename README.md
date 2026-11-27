# Stromy v mapě – aplikace 2.25.10

Aplikace pro evidenci stromů a porostních ploch, fotografie, historii, import/export a volitelnou veřejnou mapu. Prezentační weby ani zákaznická data nejsou součástí.

## Co aplikace nabízí

- Evidenci stromů a porostních ploch v mapě a tabulkách, vlastní struktury formulářů a historii hodnocení.
- Fotografie s popisky, import dat a fotografií, export včetně podkladů pro QGIS.
- Správu projektů a uživatelských oprávnění.
- Volitelnou veřejnou mapu se správcem vybranými objekty a údaji, fotografiemi a podněty obyvatel.
- Podepsané aktualizační balíčky, jejichž instalaci schvaluje správce.

## Nová instalace

**[Stáhnout instalační ZIP 2.25.8](https://github.com/jirivis/stromy-v-mape/releases/download/v2.25.8/instalace-stromy-v-mape-2.25.8.zip)** · [Stránka vydání a soubory ke stažení](https://github.com/jirivis/stromy-v-mape/releases/tag/v2.25.8)

Pro nový web a prázdnou databázi. Rozbalte ZIP a postupujte podle **CTI_ME.md**. Obsah složky `application/` nahrajte do kořene samostatné domény nebo subdomény. Vyplňte `private/config.php` a otevřete `/install/`; instalátor vytvoří tabulky, číselníky a prvního správce.

**Instalační ZIP nenahrávejte přes existující aplikaci ani do aktualizátoru.** Automatické odkazy GitHubu „Source code“ jsou vývojové zdroje, nikoli připravený instalační balíček.

Postup a požadavky: [docs/INSTALACE.md](docs/INSTALACE.md). Pro vývojáře: vlastní kompletní balíček lze sestavit příkazem:

```sh
python3 release-tools/package_installer.py --output /cesta/install-2.25.8.zip
```

ZIP obsahuje application/ a návod. Používá explicitní seznam souborů, nepřibaluje místní konfiguraci, fotografie, testy, podpisové soukromé klíče ani vývojové nástroje. Test čisté instalace rozbaluje tento ZIP, nikoli vývojový checkout. Vlastní kód je GPL-3.0-or-later (LICENSE a LICENSING.md). Původ původních českých názvů a kultivarů není úplně doložen. BOTANY a BioLib jsou možné zdroje podle vzpomínky autora seznamu; nejde o potvrzení licence těchto dat. Podrobnosti: database/catalogs/PROVENANCE.md.

## Aktualizace

Veřejný distribuční zdroj je jirivis/stromy-v-mape-updates. Nové instalace mají ve vzoru konfigurace tento zdroj, veřejný podpisový klíč a kontrolu vydání na dashboardu. Každou instalaci aktualizace schvaluje správce. Instalační ZIP není aktualizační balíček. SQL změny mohou vyžadovat přípravu správcem databáze.

Verze **2.25.7** přidává maximální velikost značek 48 px, volby po uložení stromů a ploch, autory taxonů na kartách ploch, okolní objekty na mapkách a přiblížení na editovanou plochu. Plochy se kreslí průhledně nad značkami; fotografie jsou pod ukládáním a nápovědy AOPK vysvětlují jednotlivé možnosti. Aktualizace z 2.25.6 nevyžaduje SQL změny.

Verze 2.25.5–2.25.6 opravují ukládání stromů, načítání fotografií v kartách, navigaci editoru a doplňování taxonomie ve zkrácených šablonách. Veřejná mapa má sjednocené značky, clusterování a filtry podle zveřejněných údajů. Aktivní filtry mají červené označení a krátkou hlášku uprostřed obrazovky; veřejná tabulka odděluje Stromy a Plochy do záložek. Oprávnění a obnova: [docs/OBNOVA_A_OPRAVNENI.md](docs/OBNOVA_A_OPRAVNENI.md). Fotografie: [docs/FOTOGRAFIE.md](docs/FOTOGRAFIE.md).

Čistou instalaci a regresní testy ověřuje CI na PHP 8.3/8.4/8.5 s MariaDB. Cílový hosting, doručování pošty a obnovu reálných záloh je potřeba ověřit samostatně. Nejde o potvrzení úplného bezpečnostního auditu. Modul ocenění AOPK zatím není implementován; databázová příprava není kalkulačka.

## Příspěvky a licence

Vlastní kód: **GPL-3.0-or-later**, viz LICENSE a LICENSING.md. Cizí komponenty: THIRD_PARTY_NOTICES.md. Příspěvky a testy: CONTRIBUTING.md. Bezpečnostní hlášení: SECURITY.md. Taxonomický původ: database/catalogs/PROVENANCE.md.

Chyby a návrhy přijímáme přes [Issues](https://github.com/jirivis/stromy-v-mape/issues), opravy přes pull requesty. Výchozím zdrojem je větev main. Bezpečnostní nálezy posílejte neveřejně podle SECURITY.md.

Verze 2.25.8 odděluje skutečná data odumření a odstranění od dne označení. Aktualizace z 2.25.7 vyžaduje SQL přípravu nabídnutou aktualizátorem; po záloze ji spusťte v phpMyAdminu a zvolte Zkontrolovat databázi. Podrobnosti: [docs/TREE_EVENT_DATES.md](docs/TREE_EVENT_DATES.md).

Verze 2.25.9 upravuje rozestupy a tlačítka formuláře, obnovuje zobrazení nových sdílených odkazů, sjednocuje značku odstraněných stromů ve veřejné mapě a doplňuje okolní objekty při ruční opravě polohy. Aktualizace z 2.25.8 nevyžaduje SQL. Poslední kompletní instalační ZIP zůstává 2.25.8; poté použijte aktualizátor.

Verze 2.25.10 sjednocuje červené zobrazení odstraněných ploch a horní stavové hlášky. Červená je vyhrazena odstraněným porostům; veřejná mapa zveřejní tento stav pouze při povoleném poli Stav. Aktualizace z 2.25.9 je bez SQL změn.
