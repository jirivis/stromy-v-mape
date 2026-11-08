<!-- 🟦 SEKCE 2 — DENDROMETRIE -->
<h2>Dendrometrie</h2>

<label for="dbh1_cm">Průměr kmene 1 (dbh1_cm)</label>
<input
    type="number"
    name="dbh1_cm"
    id="dbh1_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['dbh1_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="dbh2_cm">Průměr kmene 2 (dbh2_cm)</label>
<input
    type="number"
    name="dbh2_cm"
    id="dbh2_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['dbh2_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="dbh3_cm">Průměr kmene 3 (dbh3_cm)</label>
<input
    type="number"
    name="dbh3_cm"
    id="dbh3_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['dbh3_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="dbh4_cm">Průměr kmene 4 (dbh4_cm)</label>
<input
    type="number"
    name="dbh4_cm"
    id="dbh4_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['dbh4_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="girth1_cm">Obvod kmene 1 (girth1_cm)</label>
<input
    type="number"
    name="girth1_cm"
    id="girth1_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['girth1_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="girth2_cm">Obvod kmene 2 (girth2_cm)</label>
<input
    type="number"
    name="girth2_cm"
    id="girth2_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['girth2_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="girth3_cm">Obvod kmene 3 (girth3_cm)</label>
<input
    type="number"
    name="girth3_cm"
    id="girth3_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['girth3_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="girth4_cm">Obvod kmene 4 (girth4_cm)</label>
<input
    type="number"
    name="girth4_cm"
    id="girth4_cm"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['girth4_cm'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="height">Výška stromu</label>
<input
    type="number"
    name="height"
    id="height"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['height'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="canopy_h_m">Výška nasazení koruny</label>
<input
    type="number"
    name="canopy_h_m"
    id="canopy_h_m"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['canopy_h_m'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="spread_m">Šířka koruny</label>
<input
    type="number"
    name="spread_m"
    id="spread_m"
    step="any"
    value="<?= htmlspecialchars(
        (string)($tree['spread_m'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>