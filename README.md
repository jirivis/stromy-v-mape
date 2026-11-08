# Stromy v mapě – instalační balíček V2.23.0

Kompletní samostatná aplikace pro novou doménu/subdoménu a prázdnou databázi. Nenahrávat přes běžící produkční web. Neobsahuje prezentační weby, přístupové údaje ani zákaznická data.

Postup: docs/INSTALACE.md. Fotografie a zálohy: docs/FOTOGRAFIE.md. Provedené testy: docs/OVERENI.md.

## Uspořádání
- app/: společná konfigurace, ověření účtu, úložiště fotografií, pošta.
- private/: konfigurace a soukromé fotografie ploch; přístup zakazuje .htaccess.
- database/: schéma nové databáze a oddělené migrace.
- install/: jednorázový instalátor správce a schématu.
- maintenance/: dávkový převod fotografií ploch pro správce.
- map/public/: mapy, formuláře, historie, importy a exporty.

Aktuální hodnoty zůstávají v trees, historické snapshoty a jejich vazby se zachovávají. Tree_measurements se nemaže. Rozměry jsou celá čísla, stáří podporuje 1000 let. Existující interní ID se nepřečíslují. Exporty používají evidenční čísla objektů.

Nové schéma používá utf8mb4 a dlouhé poznámky TEXT, aby se vešlo do limitu řádku MariaDB. Rozdílné typy starších ID se na existujících databázích automaticky nepřepisují.

## Rozsah vydání
Jde o sjednocený instalační balíček pro ověření na cílovém hostingu, nikoli automatickou aktualizaci produkce. Zůstává nutné dokončit úplný bezpečnostní audit starších endpointů, zvolit licenci pro zveřejnění a ověřit práva k distribuci číselníků. Číselníky druhů a kultivarů obsahují pouze strukturu. Instalace do podadresáře zatím není podporována.

## Aktualizace
Dashboard správce nyní obsahuje Aktualizace aplikace. Podporuje kontrolu GitHub vydání, ruční ZIP, ověřené podepsané balíčky, schválení správce a databázové migrace. Zdroj a veřejný klíč je třeba nastavit až podle skutečného vydavatelského repozitáře. Podrobnosti a obnova při chybě: docs/AKTUALIZACE.md. Modul ocenění AOPK zatím není implementován.

## GitHub
Příprava repozitáře `jirivis/stromy-v-mape`: [stav a další kroky](docs/GITHUB.md).
