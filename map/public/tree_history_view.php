<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../auth_api.php';

require_once __DIR__
    . '/../../config_app.php';

requireProjectRole([
    'owner',
    'admin',
    'editor',
    'viewer'
]);

require_once __DIR__
    . '/api/db.php';

header(
    'Content-Type: text/html; charset=utf-8'
);

$mysqli->set_charset('utf8mb4');

$historyId =
    (int) (
        $_GET['id']
        ?? 0
    );

if ($historyId <= 0) {

    http_response_code(400);

    die(
        'Chybí ID historického záznamu.'
    );
}


$stmt = $mysqli->prepare(
    'SELECT
        h.*,
        u.username
     FROM tree_history h
     LEFT JOIN users u
        ON u.id = h.snapshot_by_user_id
     WHERE h.id = ?
       AND h.project_id = ?
     LIMIT 1'
);

if (!$stmt) {

    http_response_code(500);

    die(
        'Historický záznam se nepodařilo načíst.'
    );
}


$stmt->bind_param(
    'ii',
    $historyId,
    $activeProjectId
);

$stmt->execute();

$history =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();


if (!$history) {

    http_response_code(404);

    die(
        'Historický záznam nebyl nalezen.'
    );
}


try {

    $tree =
        json_decode(
            (string) $history['tree_data'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

} catch (Throwable $e) {

    http_response_code(500);

    die(
        'Historický záznam obsahuje neplatná data.'
    );
}


/*
 * Projekt zobrazíme stejně jako
 * na běžné kartě stromu.
 */
$projectName =
    $_SESSION['active_project_name']
    ?? '';

/*
 * Fotografie uložené k historickému snapshotu.
 *
 * Načítáme pouze fotografie, které byly
 * se snapshotem propojené v okamžiku jeho vytvoření.
 */
$gallery = [];

$stmtPhotos = $mysqli->prepare(
    'SELECT
        p.id,
        p.url,
        p.note_photo,
        p.sort_order
     FROM tree_history_photos hp
     INNER JOIN trees_photo p
        ON p.id = hp.photo_id
     WHERE hp.history_id = ?
     ORDER BY p.sort_order ASC, p.id ASC'
);

if (!$stmtPhotos) {

    http_response_code(500);

    die(
        'Historické fotografie se nepodařilo načíst.'
    );
}

$stmtPhotos->bind_param(
    'i',
    $historyId
);

$stmtPhotos->execute();

$resultPhotos =
    $stmtPhotos->get_result();

while (
    $photo =
        $resultPhotos->fetch_assoc()
) {

    $gallery[] = [
        'id' =>
            (int) $photo['id'],

        'url' =>
            $photo['url'] ?? '',

        'note_photo' =>
            $photo['note_photo'] ?? '',

        'sort_order' =>
            (int) ($photo['sort_order'] ?? 0)
    ];
}

$stmtPhotos->close();

/*
 * Speciální režim společné karty.
 */
$cardMode = 'history';

$historyBackUrl =
    'tree_new.php?id='
    . (int) $history['tree_id']
    . '#tree-history';

$historyMeta = [
    'snapshot_at' =>
        $history['snapshot_at'] ?? null,

    'username' =>
        $history['username'] ?? null,

    'history_note' =>
        $history['history_note'] ?? null,

    'event_type' =>
        $history['event_type'] ?? null
];


/*
 * Historický snapshot už máme v proměnné $tree.
 * Použijeme tedy stejnou kartu jako
 * pro současný stav stromu.
 */
require __DIR__
    . '/tree_card_template.php';