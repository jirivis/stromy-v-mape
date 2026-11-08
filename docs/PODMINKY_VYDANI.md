# Podmínky před veřejným vydáním

## Bezpečnost
Opravena kontrola přihlášení, projektu, role a CSRF ve starším API tree_measurements/save_tree_measurement. HTTP test pokrývá anonymní přístup, viewer, chybějící token, cizí projekt, povolený zápis a autora. Lokálně prošlo PHP 8.3 / MariaDB 10.11. CI matice 8.3/8.4/8.5 úspěšně prošla pro commit de21b67cc1365b18a01195de1f1f5de3299d8faf (běh 42479880322). Nejde o test všech webových funkcí. Následně doplněna CSRF ochrana mazání stromu a uložení historie; lokálně zatím ověřena syntaxe.

Balíček 2.24.0 vytvořený před touto opravou ji neobsahuje. Není doporučen k novému zákaznickému nasazení. Nevydávat další balíček bez zahrnutí opravy do instalačního i aktualizačního payloadu a ověření podpisu. Celkový bezpečnostní audit tímto není dokončen; zbývají ostatní zápisové endpointy, izolace projektů, přístup k fotografiím, přihlašování a obnova hesel.

## Číselníky – konkrétní chybějící vstup
Dodané strvmap.sql obsahuje strukturu species/cultivars bez INSERT dat. Pro zabalení stávajícího číselníku je nutný export pouze těchto dvou tabulek včetně dat. Nepotřebujeme zákaznické projekty, stromy, účty ani fotografie. Nezařazovat syntetické testovací číselníky. Před veřejnou distribucí doložit původ a možnost redistribuce dat.

Následná implementace: verze číselníku, import s náhledem, doplnění chybějících položek bez přepisování místních doplnění, mapování vazeb kultivarů na druhy; opakovaný import musí být bezpečný. Číselná ID z jiné databáze se nesmějí slepě přebírat do neprázdné instalace. Tato obsluha zatím není implementovaná.

## Aktualizace – konkrétní nedokončené kroky
Podepsaný ručně nahraný balíček a databázová příprava jsou implementované a otestované. Soukromý klíč byl předán vydavateli odděleně. Zdroj je stále soukromý; veřejné release.json a ZIP v GitHub Releases zatím nejsou dostupné. Kontrola aktualizací je proto ve výchozí konfiguraci vypnutá.

Po bezpečnostní kontrole: připravit opravené balíčky, ověřit PHP 8.4, vyřešit licenci, zveřejnit zdroj nebo samostatný veřejný distribuční repozitář, publikovat podepsaný ZIP a release.json, ověřit stažení z hostingu a teprve poté zapnout kontrolu. Každé provedení schvaluje správce instalace. SQL příprava dosud není bezobslužná.
