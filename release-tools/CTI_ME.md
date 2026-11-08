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
 --migration database/migrations/2026_11_nova_funkce.sql \
 --download-url https://github.com/jirivis/stromy-v-mape/releases/download/v2.24.0/update-2.24.0.zip \
 --output update-2.24.0.zip
```

Pokud nejsou migrace, parametr --migration vynechte. Před vydáním proveďte aktualizaci na kopii databáze i simulaci selhání. Nahrajte ZIP do GitHub Releases a v hlavní větvi main zveřejněte vygenerovaný release.json. Názvy jirivis/stromy-v-mape nahraďte skutečným repozitářem. Nástroj nic nezveřejňuje automaticky.

Manifest podepisuje verzi, výchozí verzi, popis, seznam migrací a SHA-256 každého souboru. ZIP mimo manifest a podpis obsahuje pouze payload. Běžný instalační ZIP není aktualizačním balíčkem a aktualizátor jej odmítne.
