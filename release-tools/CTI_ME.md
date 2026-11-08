# Vydávání aktualizací

Soukromý podpisový klíč je pouze u vydavatele, nikdy v aplikaci, v repozitáři ani v aktualizačním ZIPu. Uživatelé instalací dostávají veřejný klíč důvěryhodnou cestou.

Jednou na vlastním počítači vytvořte RSA klíč:

```sh
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -out publisher-private.pem
openssl pkey -in publisher-private.pem -pubout -out publisher-public.pem
```

Soukromý klíč bezpečně zálohujte mimo veřejné zdrojové kódy. Nepřikládejte ho do této složky při zveřejnění. Změna důvěryhodného veřejného klíče se provádí ručně v konfiguraci zákazníka.

Připravte složku payload obsahující jen nové nebo změněné aplikační soubory se stejnými relativními cestami. Neukládejte do ní private, fotografie, install ani app/VERSION. Do database/migrations patří pouze otestované idempotentní migrace. Příklad rozšíření: ADD COLUMN IF NOT EXISTS. Aktualizátor umí nové soubory a nahrazení; odstraňování souborů nepodporuje.

```sh
python3 build.py --payload payload --from-version 2.23.0 --version 2.24.0 \
 --key /bezpecne-misto/publisher-private.pem --notes "Popis změn" \
 --migration database/migrations/2026_11_nova_funkce.sql --schema-checks checks.json \
 --download-url https://github.com/jirivis/stromy-v-mape/releases/download/v2.24.0/update-2.24.0.zip \
 --output update-2.24.0.zip
```

Pokud nejsou migrace, parametr --migration vynechte. Před vydáním proveďte aktualizaci na kopii databáze i simulaci selhání. Nahrajte ZIP do GitHub Releases a v hlavní větvi main zveřejněte vygenerovaný release.json. Názvy jirivis/stromy-v-mape nahraďte skutečným repozitářem. Nástroj nic nezveřejňuje automaticky.

Manifest podepisuje verzi, výchozí verzi, popis, seznam migrací a SHA-256 každého souboru. ZIP mimo manifest a podpis obsahuje pouze payload. Běžný instalační ZIP není aktualizačním balíčkem a aktualizátor jej odmítne.

## Příprava databáze na sdíleném hostingu
Pouze zpětně kompatibilní, opakovatelné přidávání struktury. Pro každou SQL změnu přidejte úplné kontroly výsledných sloupců do JSON a použijte `--schema-checks checks.json` spolu s `--migration database/migrations/....sql`. Příklad:

```json
[{"table":"svm_area_photos","column":"file_path","type":"varchar(80)","nullable":true}]
```

Typ musí přesně odpovídat `information_schema.COLUMNS.COLUMN_TYPE` cílové databáze. Balíček podepisuje i tyto kontroly. Web SQL pouze nabídne ke stažení a pak ověří strukturu; příkazy provede správce samostatně v phpMyAdminu. Datové migrace, indexy, přejmenování a destruktivní změny tento protokol neověřuje. Neoznačujte je jako `manual-schema`.

Nejprve je nutné nainstalovat aktualizátor s podporou tohoto protokolu balíčkem **bez migrací**. Původní 2.23.0 spouštěla migrace automaticky a nový protokol nezná. První podepsané vydání proto musí obsahovat jen aktualizátor a související soubory, žádné SQL migrace. Produkční vydání a klíč zatím nejsou připravené.

## První vydání 2.23.1
`python3 release-tools/prepare_first.py --key /bezpecne-misto/publisher-private.pem --output-dir /vystup/2.23.1` připraví podepsaný update ZIP, release.json a jednorázový bootstrap pro původní 2.23.0. Nic nenahrává na GitHub ani hosting. Výstupní adresář nesmí obsahovat dřívější balíčky.

**Původní 2.23.0 na WEDOSu vyžaduje nejprve bootstrap přes FTP:** i bez migrací prováděla CREATE TABLE, takže samotný nový podepsaný ZIP tuto chybu neobejde. Nahrají se pouze app/update.php a app/update_database.php, poté se nastaví veřejný podpisový klíč a provede podepsaná aktualizace v aplikaci. Záloha a přerušení práce uživatelů platí i pro tento krok.

Kontrola vydání vyžaduje veřejně dostupný repozitář a release.json, připojení HTTPS a správně nastavený veřejný klíč. Současný soukromý repozitář bez veřejného vydání nestačí. SQL změny i nadále vyžadují přípravu správcem databáze; nejde o bezobslužnou aktualizaci každého typu změny.
