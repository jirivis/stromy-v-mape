<!-- 🟫 SEKCE 5 — ČASOVÉ ÚDAJE -->
<h2>Časové údaje</h2>

<label for="planted">
    Datum výsadby
</label>

<input
    type="date"
    name="planted"
    id="planted"
    value="<?= htmlspecialchars(
        (string) ($tree['planted'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="measured_at">
    Datum měření
</label>

<input
    type="date"
    name="measured_at"
    id="measured_at"
    value="<?= htmlspecialchars(
        (string) ($tree['measured_at'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="last_insp_at">
    Poslední kontrola
</label>

<input
    type="date"
    name="last_insp_at"
    id="last_insp_at"
    value="<?= htmlspecialchars(
        (string) ($tree['last_insp_at'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="next_insp_at">
    Další kontrola
</label>

<input
    type="date"
    name="next_insp_at"
    id="next_insp_at"
    value="<?= htmlspecialchars(
        (string) ($tree['next_insp_at'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>