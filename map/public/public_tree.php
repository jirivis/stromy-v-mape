<?php

declare(strict_types=1);

require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

$mysqli->set_charset('utf8mb4');

$token =
    trim(
        (string) (
            $_GET['token']
            ?? ''
        )
    );

if (
    $token === ''
    || !preg_match(
        '/^[a-f0-9]{64}$/',
        $token
    )
) {
    http_response_code(404);
    die('Sdílená karta nebyla nalezena.');
}

$tokenHash =
    hash(
        'sha256',
        $token
    );


/*
 * Platný aktivní odkaz a jeho strom.
 */
$treeStatement = $mysqli->prepare(
    'SELECT
        t.*,
        p.name AS project_name,
        ts.id AS share_id
     FROM tree_shares ts
     INNER JOIN trees t
        ON t.id = ts.tree_id
     INNER JOIN projects p
        ON p.id = t.project_id
     WHERE ts.token_hash = ?
       AND ts.active = 1
       AND (
            ts.expires_at IS NULL
            OR ts.expires_at > NOW()
       )
     LIMIT 1'
);

if (!$treeStatement) {
    http_response_code(500);
    die('Kartu se nepodařilo načíst.');
}

$treeStatement->bind_param(
    's',
    $tokenHash
);

$treeStatement->execute();

$treeResult =
    $treeStatement->get_result();

$tree =
    $treeResult->fetch_assoc();

$treeStatement->close();

if (!$tree) {
    http_response_code(404);
    die('Sdílená karta nebyla nalezena nebo už neplatí.');
}

$treeId =
    (int) $tree['id'];

$shareId =
    (int) $tree['share_id'];


/*
 * Galerie fotografií.
 */
$photoStatement = $mysqli->prepare(
    'SELECT
        id,
        url,
        note_photo,
        sort_order
     FROM trees_photo
     WHERE tree_id = ?
       AND status = \'active\'
     ORDER BY
        sort_order,
        id'
);

if (!$photoStatement) {
    http_response_code(500);
    die('Fotografie se nepodařilo načíst.');
}

$photoStatement->bind_param(
    'i',
    $treeId
);

$photoStatement->execute();

$photoResult =
    $photoStatement->get_result();

$gallery = [];

while (
    $photo =
        $photoResult->fetch_assoc()
) {
    $photo['url']='/map/public/photo.php?photo='.(int)$photo['id'].'&token='.rawurlencode($token);
    $gallery[] = $photo;
}

$photoStatement->close();


/*
 * Záznam posledního otevření.
 */
$accessStatement = $mysqli->prepare(
    'UPDATE tree_shares
     SET last_access_at = NOW()
     WHERE id = ?'
);

if ($accessStatement) {
    $accessStatement->bind_param(
        'i',
        $shareId
    );

    $accessStatement->execute();
    $accessStatement->close();
}


/*
 * Společná šablona karty.
 */
$cardMode = 'public';

$projectName =
    (string) (
        $tree['project_name']
        ?? ''
    );

require __DIR__
    . '/tree_card_template.php';