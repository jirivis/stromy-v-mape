<?php

declare(strict_types=1);

/**
 * Uloží kompletní aktuální stav stromu
 * do tabulky tree_history.
 *
 * Vrací ID vytvořeného historického záznamu.
 */
function createTreeHistorySnapshot(
    mysqli $mysqli,
    int $treeId,
    int $userId,
    string $eventType = 'manual',
    ?string $historyNote = null
): int {

    /*
     * 1. Načteme kompletní aktuální řádek stromu.
     */
    $stmt = $mysqli->prepare(
        'SELECT *
         FROM trees
         WHERE id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Nepodařilo se připravit načtení stromu: '
            . $mysqli->error
        );
    }

    $stmt->bind_param(
        'i',
        $treeId
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;
        $stmt->close();

        throw new RuntimeException(
            'Nepodařilo se načíst strom: '
            . $error
        );
    }

    $tree =
        $stmt
            ->get_result()
            ->fetch_assoc();

    $stmt->close();

    if (!$tree) {
        throw new RuntimeException(
            'Strom nebyl nalezen.'
        );
    }

    /*
     * 2. Projekt převezmeme přímo ze stromu.
     */
    $projectId =
        (int) ($tree['project_id'] ?? 0);

    if ($projectId <= 0) {
        throw new RuntimeException(
            'Strom nemá platné project_id.'
        );
    }

    /*
     * 3. Kompletní stav stromu převedeme do JSON.
     *
     * Díky tomu se automaticky historizují
     * všechny současné sloupce tabulky trees.
     */
    $treeData = json_encode(
        $tree,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_THROW_ON_ERROR
    );

    /*
     * 4. Uložíme snapshot.
     */
    $historyStmt = $mysqli->prepare(
        'INSERT INTO tree_history (
            tree_id,
            project_id,
            snapshot_by_user_id,
            event_type,
            history_note,
            tree_data
         )
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    if (!$historyStmt) {
        throw new RuntimeException(
            'Nepodařilo se připravit uložení historie: '
            . $mysqli->error
        );
    }

    $historyStmt->bind_param(
        'iiisss',
        $treeId,
        $projectId,
        $userId,
        $eventType,
        $historyNote,
        $treeData
    );

    if (!$historyStmt->execute()) {

        $error = $historyStmt->error;
        $historyStmt->close();

        throw new RuntimeException(
            'Nepodařilo se uložit historii stromu: '
            . $error
        );
    }

    $historyId =
        (int) $historyStmt->insert_id;

    $historyStmt->close();
	
	/*
 * 5. K historickému snapshotu uložíme
 * fotografie, které jsou ke stromu
 * připojené právě v tomto okamžiku.
 */
$photoStmt = $mysqli->prepare("
    SELECT
        id,
        url,
        note_photo,
        sort_order
    FROM trees_photo
    WHERE tree_id = ?
      AND status = 'active'
    ORDER BY sort_order ASC, id ASC
");

if (!$photoStmt) {
    throw new RuntimeException(
        'Nepodařilo se připravit načtení fotografií: '
        . $mysqli->error
    );
}

$photoStmt->bind_param(
    'i',
    $treeId
);

if (!$photoStmt->execute()) {

    $error = $photoStmt->error;
    $photoStmt->close();

    throw new RuntimeException(
        'Nepodařilo se načíst fotografie stromu: '
        . $error
    );
}

$photoResult =
    $photoStmt->get_result();

$historyPhotoStmt = $mysqli->prepare(
    'INSERT INTO tree_history_photos (
        history_id,
        photo_id
     )
     VALUES (?, ?)'
);

if (!$historyPhotoStmt) {

    $photoStmt->close();

    throw new RuntimeException(
        'Nepodařilo se připravit uložení historických fotografií: '
        . $mysqli->error
    );
}

while (
    $photo =
        $photoResult->fetch_assoc()
) {

    $photoId =
        (int) $photo['id'];

    $historyPhotoStmt->bind_param(
        'ii',
        $historyId,
        $photoId
    );

    if (!$historyPhotoStmt->execute()) {

        $error =
            $historyPhotoStmt->error;

        $historyPhotoStmt->close();
        $photoStmt->close();

        throw new RuntimeException(
            'Nepodařilo se uložit historickou fotografii: '
            . $error
        );
    }
}

$historyPhotoStmt->close();
$photoStmt->close();

return $historyId;

    return $historyId;
}