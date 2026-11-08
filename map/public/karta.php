<?php

declare(strict_types=1);
require_once __DIR__ . '/project_tools/page_access.php';
svmRequirePageLogin();

/*
---------------------------------------------------
 Stromy v mapě
 Interní karta stromu
---------------------------------------------------
*/

require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');

$mysqli->set_charset('utf8mb4');

$treeId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$treeId || $treeId < 1) {
    http_response_code(400);
    die('Chybí platné ID stromu.');
}

/*
 * Strom lze načíst pouze z aktivního projektu.
 * Platí to také pro superadministrátora.
 */
$treeStatement = $mysqli->prepare(
    'SELECT *
     FROM trees
     WHERE id = ?
       AND project_id = ?
     LIMIT 1'
);

if (!$treeStatement) {
    http_response_code(500);

    die(
        'Kartu stromu se nepodařilo připravit.'
    );
}

$treeStatement->bind_param(
    'ii',
    $treeId,
    $activeProjectId
);

$treeStatement->execute();

$treeResult =
    $treeStatement->get_result();

$tree =
    $treeResult->fetch_assoc();

$treeStatement->close();

if (!$tree) {
    http_response_code(404);

    die(
        'Strom nebyl nalezen v aktivním projektu.'
    );
}

/*
 * Fotografie stromu.
 */
$photoStatement = $mysqli->prepare(
    'SELECT
        tp.id,
        tp.url,
        tp.note_photo,
        tp.sort_order
     FROM trees_photo tp
     INNER JOIN trees t
        ON t.id = tp.tree_id
     WHERE tp.tree_id = ?
       AND t.project_id = ?
       AND tp.status = \'active\'
     ORDER BY
        tp.sort_order,
        tp.id'
);

if (!$photoStatement) {
    http_response_code(500);

    die(
        'Fotografie stromu se nepodařilo načíst.'
    );
}

$photoStatement->bind_param(
    'ii',
    $treeId,
    $activeProjectId
);

$photoStatement->execute();

$photoResult =
    $photoStatement->get_result();

$gallery = [];

while (
    $photo =
        $photoResult->fetch_assoc()
) {
    $gallery[] = $photo;
}

$photoStatement->close();

/*
 * Údaje pro společnou šablonu.
 */
$cardMode = 'internal';

$projectName =
    $activeProjectName
    ?? '';

if(($_GET['without_photos']??'')==='1')$gallery=[];
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
require __DIR__
    . '/tree_card_template.php';