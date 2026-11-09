<?php

declare(strict_types=1);

/*
---------------------------------------------------
 Stromy v mapě
 Soubor: auth_api.php
 Účel:
 - ochrana API endpointů,
 - kontrola aktivního účtu,
 - kontrola aktivního projektu,
 - ověření přístupu uživatele k projektu.
---------------------------------------------------
*/

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header(
    'Content-Type: application/json; charset=utf-8'
);


/*
 * Jednotná odpověď při chybě.
 */
function apiAuthError(
    int $statusCode,
    string $error,
    string $message
): never {

    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => false,
            'error' => $error,
            'message' => $message
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
 * Přihlášení.
 */
if (!isset($_SESSION['user_id'])) {

    apiAuthError(
        401,
        'unauthorized',
        'Pro použití této funkce je nutné přihlášení.'
    );
}


/*
 * Aktivní účet.
 */
if (
    isset($_SESSION['active']) &&
    (int) $_SESSION['active'] !== 1
) {

    apiAuthError(
        403,
        'inactive_account',
        'Uživatelský účet není aktivní.'
    );
}


/*
 * Aktivní projekt.
 */
$activeProjectId =
    (int) (
        $_SESSION['active_project_id']
        ?? 0
    );

if ($activeProjectId < 1) {

    apiAuthError(
        409,
        'project_not_selected',
        'Nejprve vyberte aktivní projekt.'
    );
}


/*
 * Připojení k databázi.
 *
 * require_once zabrání dvojímu načtení,
 * pokud endpoint db.php načítá také.
 */
require_once __DIR__
    . '/map/public/api/db.php';


/*
 * Podpora názvu $mysqli i $conn.
 */
if (
    isset($mysqli) &&
    $mysqli instanceof mysqli
) {

    $authDb = $mysqli;

} elseif (
    isset($conn) &&
    $conn instanceof mysqli
) {

    $authDb = $conn;

} else {

    apiAuthError(
        500,
        'database_connection_missing',
        'Databázové připojení není dostupné.'
    );
}


require_once __DIR__.'/app/account.php';
if(!svmApplyAccount(svmLoadAccount($authDb,(int)$_SESSION['user_id']))){apiAuthError(403,'inactive_account','Přihlášení již není platné nebo účet není aktivní. Přihlaste se znovu.');}

$userId =
    (int) $_SESSION['user_id'];

$isSuperAdmin =
    (int) (
        $_SESSION['is_super_admin']
        ?? 0
    );


/*
 * Superadministrátor může pracovat
 * se všemi existujícími projekty.
 */
if ($isSuperAdmin === 1) {

    $projectStatement =
        $authDb->prepare(
            'SELECT id, name
             FROM projects
             WHERE id = ?
             LIMIT 1'
        );

    if (!$projectStatement) {

        apiAuthError(
            500,
            'project_check_failed',
            'Kontrolu projektu se nepodařilo připravit.'
        );
    }

    $projectStatement->bind_param(
        'i',
        $activeProjectId
    );

} else {

    /*
     * Běžný uživatel musí být:
     * - vlastníkem projektu,
     * - nebo uveden v project_users.
     */
    $projectStatement =
        $authDb->prepare(
            'SELECT DISTINCT
                p.id,
                p.name,
                CASE
                    WHEN p.owner_id = ?
                        THEN "owner"
                    ELSE pu.project_role
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

    if (!$projectStatement) {

        apiAuthError(
            500,
            'project_check_failed',
            'Kontrolu přístupu k projektu se nepodařilo připravit.'
        );
    }

    $projectStatement->bind_param(
        'iiiii',
        $userId,
        $userId,
        $activeProjectId,
        $userId,
        $userId
    );
}


$projectStatement->execute();

$projectResult =
    $projectStatement->get_result();

$activeProject =
    $projectResult->fetch_assoc();

$projectStatement->close();


if (!$activeProject) {

    /*
     * Zrušíme neplatný projekt v relaci,
     * aby se nepoužíval v dalších požadavcích.
     */
    unset(
        $_SESSION['active_project_id'],
        $_SESSION['active_project_name'],
        $_SESSION['active_project_role']
    );

    apiAuthError(
        403,
        'project_access_denied',
        'K vybranému projektu nemáte přístup.'
    );
}


/*
 * Hodnoty, které mohou následně používat
 * jednotlivé API endpointy.
 */
$activeProjectId =
    (int) $activeProject['id'];

$activeProjectName =
    (string) $activeProject['name'];

$activeProjectRole =
    $isSuperAdmin === 1
        ? 'admin'
        : (
            $activeProject['project_role']
            ?? 'viewer'
        );


/*
 * Obnovíme údaje v session podle databáze.
 */
$_SESSION['active_project_id'] =
    $activeProjectId;

$_SESSION['active_project_name'] =
    $activeProjectName;

$_SESSION['active_project_role'] =
    $activeProjectRole;
	
	function requireProjectRole(
    array $allowedRoles
): void {

    global $activeProjectRole;

    if (
        !in_array(
            $activeProjectRole,
            $allowedRoles,
            true
        )
    ) {
        apiAuthError(
            403,
            'insufficient_project_role',
            'Pro tuto akci nemáte dostatečné oprávnění.'
        );
    }
}