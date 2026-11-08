<!-- 🟩 SEKCE 1 — IDENTIFIKACE -->
<h2>Identifikace stromu</h2>

<?php

/*
 * Při editaci zachováme současné číslo stromu.
 *
 * U nového stromu zjistíme nejvyšší usr_id
 * pouze v AKTIVNÍM PROJEKTU.
 */
if ($is_edit) {

    $lastId =
        (int) (
            $tree['usr_id']
            ?? 0
        );

} else {

    $lastIdStmt =
        $mysqli->prepare("
            SELECT
                COALESCE(
                    MAX(usr_id),
                    0
                ) AS max_id
            FROM trees
            WHERE project_id = ?
        ");

    if (!$lastIdStmt) {

        die(
            "Chyba při přípravě načtení "
            . "posledního usr_id: "
            . $mysqli->error
        );
    }

    $lastIdStmt->bind_param(
        "i",
        $activeProjectId
    );

    if (!$lastIdStmt->execute()) {

        die(
            "Chyba při načítání posledního "
            . "usr_id: "
            . $lastIdStmt->error
        );
    }

    $result =
        $lastIdStmt->get_result();

    $row =
        $result->fetch_assoc();

    $lastId =
        (int) (
            $row['max_id']
            ?? 0
        );

    $lastIdStmt->close();
}

?>

<input
    type="hidden"
    id="last_usr_id"
    value="<?= (int) $lastId ?>"
>

<label for="usr_id">
    Uživatelské číslo stromu (usr_id)
</label>

<input
    type="number"
    name="usr_id"
    id="usr_id"
    required
    value="<?= htmlspecialchars(
        (string) ($tree['usr_id'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="species">
    Vědecké jméno
</label>

<div class="suggest-wrapper">

    <input
        type="text"
        name="species"
        id="species"
        autocomplete="off"
        value="<?= htmlspecialchars(
            (string) ($tree['species'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <div
        id="species-suggestions"
        class="suggestions"
    ></div>

</div>

<label for="scientific_author">
    Autor vědeckého jména
</label>

<input
    type="text"
    name="scientific_author"
    id="scientific_author"
    value="<?= htmlspecialchars(
        (string) ($tree['scientific_author'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="czech">
    Český název
</label>

<div class="suggest-wrapper">
<input
    type="text"
    name="czech"
    id="czech" autocomplete="off"
    value="<?= htmlspecialchars(
        (string) ($tree['czech'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
<div id="czech-suggestions" class="suggestions"></div>
</div>

<label for="angio_gymno">
    angio/gymno
</label>

<select
    name="angio_gymno"
    id="angio_gymno"
>

    <option value="">
        — vyber —
    </option>

    <option
        value="angiospermae"
        <?= (($tree['angio_gymno'] ?? '') === 'angiospermae')
            ? 'selected'
            : '' ?>
    >
        angiospermae (listnáče / krytosemenné)
    </option>

    <option
        value="gymnospermae"
        <?= (($tree['angio_gymno'] ?? '') === 'gymnospermae')
            ? 'selected'
            : '' ?>
    >
        gymnospermae (jehličnany / nahosemenné)
    </option>

</select>

<!-- CULTIVAR AUTOCOMPLETE -->

<label for="cultivar">
    Kultivar
</label>

<div class="suggest-wrapper">

    <input
        type="text"
        id="cultivar"
        name="cultivated_variety"
        autocomplete="off"
        value="<?= htmlspecialchars(
            (string) ($tree['cultivated_variety'] ?? ''),
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

    <div
        id="cultivar-suggestions"
        class="suggestions"
    ></div>

</div>