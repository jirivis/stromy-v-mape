<!-- 🟨 SEKCE 3 — VĚK A ZDRAVOTNÍ STAV -->
<h2>Hodnocení stromu</h2>

<label for="phy_age">Fyziologický věk</label>
<select name="phy_age" id="phy_age">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['phy_age'] ?? '') == '1') ? 'selected' : '' ?>>
        1 - mladý jedinec ve fázi ujímání
    </option>

    <option value="2" <?= (($tree['phy_age'] ?? '') == '2') ? 'selected' : '' ?>>
        2 - aklimatizovaný mladý strom
    </option>

    <option value="3" <?= (($tree['phy_age'] ?? '') == '3') ? 'selected' : '' ?>>
        3 - dospívající jedinec
    </option>

    <option value="4" <?= (($tree['phy_age'] ?? '') == '4') ? 'selected' : '' ?>>
        4 - dospělý jedinec
    </option>

    <option value="5" <?= (($tree['phy_age'] ?? '') == '5') ? 'selected' : '' ?>>
        5 - senescentní jedinec
    </option>
</select>


<label for="age">Odhadovaný věk</label>
<input
    type="number"
    name="age"
    id="age"
    min="0" max="32767" step="1"
    value="<?= htmlspecialchars(
        (string)($tree['age'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>


<label for="vitality">Vitalita</label>
<select name="vitality" id="vitality">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['vitality'] ?? '') == '1') ? 'selected' : '' ?>>
        1 - výborná až mírně snížená
    </option>

    <option value="2" <?= (($tree['vitality'] ?? '') == '2') ? 'selected' : '' ?>>
        2 - zřetelně snížená
    </option>

    <option value="3" <?= (($tree['vitality'] ?? '') == '3') ? 'selected' : '' ?>>
        3 - výrazně snížená
    </option>

    <option value="4" <?= (($tree['vitality'] ?? '') == '4') ? 'selected' : '' ?>>
        4 - zbytková
    </option>

    <option value="5" <?= (($tree['vitality'] ?? '') == '5') ? 'selected' : '' ?>>
        5 - suchý (mrtvý) strom
    </option>
</select>


<label for="death_date">Datum odumření (pokud je známé)</label>
<input type="date" name="death_date" id="death_date" value="<?= htmlspecialchars((string)($tree['death_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<p class="hint">Nevyplněné datum nenahrazujeme datem zápisu. Při změně vitality na 5 se automaticky zaznamená den označení.</p>
<label for="tree_health">Zdravotní stav</label>
<select name="tree_health" id="tree_health">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['tree_health'] ?? '') == '1') ? 'selected' : '' ?>>
        1 – výborný až dobrý
    </option>

    <option value="2" <?= (($tree['tree_health'] ?? '') == '2') ? 'selected' : '' ?>>
        2 – zhoršený
    </option>

    <option value="3" <?= (($tree['tree_health'] ?? '') == '3') ? 'selected' : '' ?>>
        3 – výrazně zhoršený
    </option>

    <option value="4" <?= (($tree['tree_health'] ?? '') == '4') ? 'selected' : '' ?>>
        4 – silně narušený
    </option>

    <option value="5" <?= (($tree['tree_health'] ?? '') == '5') ? 'selected' : '' ?>>
        5 – kritický/rozpadlý strom
    </option>
</select>


<label for="stability">Statická stabilita</label>
<select name="stability" id="stability">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['stability'] ?? '') == '1') ? 'selected' : '' ?>>
        1 – výborná až dobrá (nenarušená)
    </option>

    <option value="2" <?= (($tree['stability'] ?? '') == '2') ? 'selected' : '' ?>>
        2 – zhoršená
    </option>

    <option value="3" <?= (($tree['stability'] ?? '') == '3') ? 'selected' : '' ?>>
        3 – výrazně zhoršená
    </option>

    <option value="4" <?= (($tree['stability'] ?? '') == '4') ? 'selected' : '' ?>>
        4 – silně narušená
    </option>

    <option value="5" <?= (($tree['stability'] ?? '') == '5') ? 'selected' : '' ?>>
        5 – kritická
    </option>
</select>


<label for="operational_safety">Provozní bezpečnost</label>
<select name="operational_safety" id="operational_safety">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['operational_safety'] ?? '') == '1') ? 'selected' : '' ?>>
        1 – bezpečný
    </option>

    <option value="2" <?= (($tree['operational_safety'] ?? '') == '2') ? 'selected' : '' ?>>
        2 – potenciálně rizikový
    </option>

    <option value="3" <?= (($tree['operational_safety'] ?? '') == '3') ? 'selected' : '' ?>>
        3 – nebezpečný
    </option>
</select>


<label for="longevity">Perspektiva</label>
<select name="longevity" id="longevity">
    <option value="">— vyber —</option>

    <option value="a" <?= (($tree['longevity'] ?? '') === 'a') ? 'selected' : '' ?>>
        a – dlouhodobě perspektivní
    </option>

    <option value="b" <?= (($tree['longevity'] ?? '') === 'b') ? 'selected' : '' ?>>
        b – krátkodobě perspektivní (perspektiva dočasná)
    </option>

    <option value="c" <?= (($tree['longevity'] ?? '') === 'c') ? 'selected' : '' ?>>
        c – neperspektivní
    </option>
</select>


<label for="ecological_value">Biologická hodnota</label>
<select name="ecological_value" id="ecological_value">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['ecological_value'] ?? '') == '1') ? 'selected' : '' ?>>
        1 – nízká
    </option>

    <option value="2" <?= (($tree['ecological_value'] ?? '') == '2') ? 'selected' : '' ?>>
        2 – střední
    </option>

    <option value="3" <?= (($tree['ecological_value'] ?? '') == '3') ? 'selected' : '' ?>>
        3 – vysoká
    </option>
</select>

<label for="landscape_value">Sadovnická hodnota</label>
<select name="landscape_value" id="landscape_value">
    <option value="">— vyber —</option>

    <option value="1" <?= (($tree['landscape_value'] ?? '') == '1') ? 'selected' : '' ?>>
        1 – jedinec velmi hodnotný
    </option>

    <option value="2" <?= (($tree['landscape_value'] ?? '') == '2') ? 'selected' : '' ?>>
        2 – jedinec nadprůměrně hodnotný
    </option>

    <option value="3" <?= (($tree['landscape_value'] ?? '') == '3') ? 'selected' : '' ?>>
        3 – jedinec průměrně hodnotný
    </option>

    <option value="4" <?= (($tree['landscape_value'] ?? '') == '4') ? 'selected' : '' ?>>
        4 – jedinec podprůměrně hodnotný
    </option>

    <option value="5" <?= (($tree['landscape_value'] ?? '') == '5') ? 'selected' : '' ?>>
        5 – jedinec velmi málo hodnotný
    </option>
</select>


<label for="note_tree">Poznámka ke stromu</label>
<textarea
    name="note_tree"
    id="note_tree"
    rows="6"
><?= htmlspecialchars(
    (string)($tree['note_tree'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
) ?></textarea>