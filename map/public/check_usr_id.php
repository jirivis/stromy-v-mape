<?php
/* Kontrola duplicity čísla stromu pouze v aktivním projektu.
 * Pouze informativní – neblokuje uložení.
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/api/db.php';

$db = $mysqli ?? $conn ?? null;
if (!$db instanceof mysqli || !isset($activeProjectId) || (int)$activeProjectId <= 0) {
    http_response_code(403);
    echo 'error';
    exit;
}
$usrId = filter_input(INPUT_GET, 'usr_id', FILTER_VALIDATE_INT);
$treeId = filter_input(INPUT_GET, 'tree_id', FILTER_VALIDATE_INT);
$treeId = $treeId === false || $treeId === null ? 0 : (int)$treeId;
if ($usrId === false || $usrId === null || $usrId <= 0) {
    echo 'ok';
    exit;
}
$stmt = $db->prepare('SELECT id FROM trees WHERE project_id = ? AND usr_id = ? AND id <> ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo 'error';
    exit;
}
$projectId = (int)$activeProjectId;
$stmt->bind_param('iii', $projectId, $usrId, $treeId);
$stmt->execute();
$stmt->store_result();
echo $stmt->num_rows > 0 ? 'exists' : 'ok';
$stmt->close();
