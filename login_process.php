<?php

declare(strict_types=1);

require_once __DIR__.'/app/auth_form.php';

require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

/*
---------------------------------------------------
 Stromy v mapě
 Soubor: login_process.php
 Účel: ověření přihlášení a volba projektu
---------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header('Location: /login.php?error=empty');
    exit;
}

$stmt = $mysqli->prepare(
    'SELECT
        id,
        username,
        password,
        role,
        is_super_admin,
        active
     FROM users
     WHERE username = ?
     LIMIT 1'
);

if (!$stmt) {
    error_log(
        'login_process.php: příprava uživatelského dotazu selhala: '
        . $mysqli->error
    );

    header('Location: /login.php?error=server');
    exit;
}

$stmt->bind_param('s', $username);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();

if (
    !$user ||
    !password_verify(
        $password,
        (string) $user['password']
    )
) {
    header('Location: /login.php?error=invalid');
    exit;
}

if ((int) $user['active'] !== 1) {
    header('Location: /login.php?error=inactive');
    exit;
}

/*
 * Ochrana proti session fixation.
 */
session_regenerate_id(true);
$_SESSION=[];
$_SESSION['password_version']=hash('sha256',(string)$user['password']);

$userId = (int) $user['id'];
$isSuperAdmin = (int) $user['is_super_admin'];

$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['is_super_admin'] = $isSuperAdmin;
$_SESSION['active'] = (int) $user['active'];

/*
 * Předchozí aktivní projekt po novém přihlášení
 * raději vždy zahodíme.
 */
unset(
    $_SESSION['active_project_id'],
    $_SESSION['active_project_name'],
    $_SESSION['active_project_role']
);

/*
 * Načteme projekty, ke kterým má uživatel přístup.
 *
 * Superadministrátor vidí všechny projekty.
 * Ostatní uživatelé:
 * - projekty uvedené v project_users,
 * - případně projekt, kde jsou přímo owner_id.
 */
if ($isSuperAdmin === 1) {

    $projectStmt = $mysqli->prepare(
        'SELECT
            id,
            name,
            "admin" AS project_role
         FROM projects
         ORDER BY name, id'
    );

} else {

    $projectStmt = $mysqli->prepare(
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
            p.owner_id = ?
            OR pu.user_id = ?
         ORDER BY p.name, p.id'
    );
}

if (!$projectStmt) {
    error_log(
        'login_process.php: načtení projektů nelze připravit: '
        . $mysqli->error
    );

    session_destroy();

    header('Location: /login.php?error=server');
    exit;
}

if ($isSuperAdmin !== 1) {
    $projectStmt->bind_param(
        'iiii',
        $userId,
        $userId,
        $userId,
        $userId
    );
}

$projectStmt->execute();

$projectResult = $projectStmt->get_result();
$projects = [];

while ($project = $projectResult->fetch_assoc()) {
    $projects[] = [
        'id' => (int) $project['id'],
        'name' => (string) $project['name'],
        'project_role' =>
            (string) $project['project_role']
    ];
}

$projectStmt->close();

/*
 * Jeden projekt aktivujeme automaticky.
 * U více projektů provede výběr dashboard.
 */
if (count($projects) === 1) {
    $_SESSION['active_project_id'] =
        $projects[0]['id'];

    $_SESSION['active_project_name'] =
        $projects[0]['name'];

    $_SESSION['active_project_role'] =
        $projects[0]['project_role'];
}

/*
 * Aktualizace posledního přihlášení.
 */
$update = $mysqli->prepare(
    'UPDATE users
     SET last_login = NOW()
     WHERE id = ?'
);

if ($update) {
    $update->bind_param('i', $userId);
    $update->execute();
    $update->close();
}

header('Location: /dashboard.php');
exit;