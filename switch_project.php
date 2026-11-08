<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/map/public/api/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard.php');
    exit;
}

$projectId = filter_input(
    INPUT_POST,
    'project_id',
    FILTER_VALIDATE_INT
);

if (!$projectId || $projectId < 1) {
    header(
        'Location: /dashboard.php?project_error=invalid'
    );
    exit;
}

$userId = (int) $_SESSION['user_id'];
$isSuperAdmin =
    (int) ($_SESSION['is_super_admin'] ?? 0);

if ($isSuperAdmin === 1) {

    $stmt = $mysqli->prepare(
        'SELECT
            id,
            name,
            "admin" AS project_role
         FROM projects
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->bind_param(
        'i',
        $projectId
    );

} else {

    $stmt = $mysqli->prepare(
        'SELECT DISTINCT
            p.id,
            p.name,
            CASE
                WHEN p.owner_id = ?
                    THEN "owner"
                ELSE COALESCE(
                    pu.project_role,
                    "viewer"
                )
            END AS project_role
         FROM projects p
         LEFT JOIN project_users pu
            ON pu.project_id = p.id
            AND pu.user_id = ?
         WHERE
            p.id = ?
            AND (
                p.owner_id = ?
                OR pu.user_id = ?
            )
         LIMIT 1'
    );

    $stmt->bind_param(
        'iiiii',
        $userId,
        $userId,
        $projectId,
        $userId,
        $userId
    );
}

if (!$stmt) {
    header(
        'Location: /dashboard.php?project_error=server'
    );
    exit;
}

$stmt->execute();

$result = $stmt->get_result();
$project = $result->fetch_assoc();

$stmt->close();

if (!$project) {
    header(
        'Location: /dashboard.php?project_error=forbidden'
    );
    exit;
}

$_SESSION['active_project_id'] =
    (int) $project['id'];

$_SESSION['active_project_name'] =
    (string) $project['name'];

$_SESSION['active_project_role'] =
    (string) $project['project_role'];

/*
 * Po výběru přesměrujeme rovnou do mapy.
 */
header('Location: /map/public/');
exit;