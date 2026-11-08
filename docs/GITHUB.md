# Repozitář a vydávání

Cílový repozitář: https://github.com/jirivis/stromy-v-mape

Tento první import představuje čistý instalační základ 2.23.0. Nenahrazujte jím
soubor po souboru existující web. Instalační archiv není aktualizační balíček.

## Aktuální stav
- Produkční přechod 2.20 → 2.23.0 už proběhl; dvě fotografie ploch byly převedeny.
- Automatické stahování vydání není dosud nakonfigurované.
- Podpisové klíče dosud nejsou nastavené. Nevytváříme nefunkční release.json.
- Repozitář zatím ponechte soukromý. Před zveřejněním vyřešte licenci a audit.
- Soubory private/config.php, poštovní konfigurace, fotografie, zálohy a data
  zákazníků se necommitují. .gitignore nechrání tajné údaje už vložené do historie.

## WEDOS
Webový DB účet nemá ALTER/CREATE oprávnění. Dosavadní aktualizátor 2.23.0
provádí SQL migrace stejným účtem a není pro tento případ dokončený.
Před vydáním balíčku s databázovými změnami je nutné doplnit oddělenou SQL
přípravu přes phpMyAdmin a kontrolu připravené struktury před změnou souborů.
Nepřidávejte admin heslo do veřejného kódu ani konfigurace vydání.

## Další kroky
1. Nahrát tento základ do soukromého repozitáře.
2. Ověřit čistou instalaci a zavést testování aktualizací s omezeným DB účtem.
3. Připravit opravu aktualizátoru a podpisové klíče vydavatele.
4. Vydat ověřený podepsaný aktualizační ZIP a teprve poté release.json.
5. Připojit zdroj a veřejný klíč na existující instalaci.

Soukromý repozitář není dostupný současnému anonymnímu stahování aktualizací.
Před komunitním provozem bude potřeba ověřený veřejný zdroj vydání.
