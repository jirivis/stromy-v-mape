<?php

declare(strict_types=1);

require_once __DIR__
    . '/../../auth_api.php';

requireProjectRole([
    'owner',
    'admin',
    'editor'
]);

require_once __DIR__
    . '/api/db.php';

require_once __DIR__
    . '/api/tree_history.php';

$mysqli->set_charset('utf8mb4');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    die(
        'Nepovolená metoda požadavku.'
    );
}


$treeId =
    isset($_POST['tree_id'])
        ? (int) $_POST['tree_id']
        : 0;

$historyNote =
    trim(
        $_POST['history_note']
        ?? ''
    );
	
$returnTo =
    trim(
        $_POST['return_to']
        ?? ''
    );	


if ($treeId <= 0) {

    http_response_code(400);

    die(
        'Chybí ID stromu.'
    );
}


/*
 * Ověříme, že strom skutečně patří
 * do právě aktivního projektu.
 */
$checkStmt = $mysqli->prepare(
    'SELECT id
     FROM trees
     WHERE id = ?
       AND project_id = ?
     LIMIT 1'
);

if (!$checkStmt) {

    http_response_code(500);

    die(
        'Nepodařilo se ověřit strom.'
    );
}

$checkStmt->bind_param(
    'ii',
    $treeId,
    $activeProjectId
);

$checkStmt->execute();

$treeExists =
    $checkStmt
        ->get_result()
        ->fetch_assoc();

$checkStmt->close();


if (!$treeExists) {

    http_response_code(404);

    die(
        'Strom nebyl nalezen v aktivním projektu.'
    );
}


/*
 * ID přihlášeného uživatele.
 */
$userId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );


try {

    createTreeHistorySnapshot(
        $mysqli,
        $treeId,
        $userId,
        'manual',
        $historyNote !== ''
            ? $historyNote
            : null
    );

} catch (Throwable $e) {

    error_log(
        'tree_history_save.php: '
        . $e->getMessage()
    );

    http_response_code(500);

    die(
        'Historický stav stromu se nepodařilo uložit.'
    );
}


/*
 * Návrat do editoru stromu.
 */
$anchor =
    $returnTo === 'photos'
        ? '#tree-photos'
        : '#tree-history';

if ($returnTo === 'photos') {

    header(
        'Location: tree_new.php?id='
        . $treeId
        . '&history_saved=1'
        . '&history_saved_for_photo=1'
        . '#tree-photos'
    );

} else {

    header(
        'Location: tree_new.php?id='
        . $treeId
        . '&history_saved=1'
        . '#tree-history'
    );
}

exit;