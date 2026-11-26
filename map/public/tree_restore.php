<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../config_app.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/tree_history.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
$_SESSION['tree_form_csrf']??=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&!hash_equals($_SESSION['tree_form_csrf'],(string)($_POST['tree_form_token']??''))){http_response_code(403);exit('Platnost formuláře vypršela. Obnovte stránku.');}

$mysqli->set_charset('utf8mb4');

$treeId =
    (int) (
        $_GET['id']
        ?? $_POST['id']
        ?? 0
    );

if ($treeId <= 0) {
    http_response_code(400);
    die('Chybí ID stromu.');
}


/*
---------------------------------------------------
 Načtení stromu
---------------------------------------------------
*/

$stmt =
    $mysqli->prepare(
        'SELECT
            id,
            project_id,
            usr_id,
            species,
            status,
            removed_at,
            removal_type,
            removal_reason
         FROM trees
         WHERE id = ?
         LIMIT 1'
    );

if (!$stmt) {
    http_response_code(500);
    die('Strom se nepodařilo načíst.');
}

$stmt->bind_param(
    'i',
    $treeId
);

$stmt->execute();

$tree =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$tree) {
    http_response_code(404);
    die('Strom nebyl nalezen.');
}


/*
---------------------------------------------------
 Projekt
---------------------------------------------------
*/

$activeProjectId =
    (int) (
        $_SESSION['active_project_id']
        ?? 0
    );

if (
    $activeProjectId <= 0
    || (int) $tree['project_id']
        !== $activeProjectId
) {
    http_response_code(403);
    die('K tomuto stromu nemáte přístup.');
}


/*
---------------------------------------------------
 Oprávnění
---------------------------------------------------
*/

$activeProjectRole =
    $_SESSION['active_project_role']
    ?? 'viewer';

$allowedRoles = [
    'owner',
    'admin',
    'editor'
];

if (
    !in_array(
        $activeProjectRole,
        $allowedRoles,
        true
    )
) {
    http_response_code(403);

    die(
        'Nemáte oprávnění obnovit tento strom.'
    );
}


$error = '';


/*
---------------------------------------------------
 Obnovení
---------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $updateStmt =
        $mysqli->prepare(
            'UPDATE trees
             SET
                status = "active",
                removed_at = NULL,
                removal_date = NULL,
                removal_marked_at = NULL,
                removal_type = NULL,
                removal_reason = NULL
             WHERE id = ?
               AND project_id = ?'
        );

    if (!$updateStmt) {

        $error =
            'Obnovení stromu se nepodařilo připravit.';

    } else {

        $updateStmt->bind_param(
            'ii',
            $treeId,
            $activeProjectId
        );

if ($updateStmt->execute()) {

    $updateStmt->close();
	
	/*
 * Automatický historický snapshot
 * po obnovení stromu.
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
        'restored',
        'Strom byl obnoven do aktivní evidence.'
    );

} catch (Throwable $e) {

    error_log(
        'tree_restore.php – history snapshot: '
        . $e->getMessage()
    );
}

    ?>
    <!DOCTYPE html>
    <html lang="cs">
    <head>
        <meta charset="UTF-8">
        <title>Aktualizace stromu</title>
    </head>

    <body>

    <script>
        if (
            window.parent
            && window.parent !== window
        ) {
            window.parent.postMessage(
                {
                    type: "tree-status-changed",
                    treeId: <?= (int) $treeId ?>
                },
                window.location.origin
            );
        }

        window.location.replace(
            "/map/public/tree_new.php?id=<?= (int) $treeId ?>"
        );
    </script>

    </body>
    </html>
    <?php

    exit;

        } else {

            $error =
                'Strom se nepodařilo obnovit.';

            $updateStmt->close();
        }
    }
}


/*
---------------------------------------------------
 Vzhled
---------------------------------------------------
*/

$pageTitle =
    'Obnovení stromu';

$pageSubtitle =
    'Správa stromu';

$cardClass =
    'admin-card admin-card--small';

require_once dirname(__DIR__, 2)
    . '/app/includes/header.php';

?>

<h2>Obnovení stromu</h2>

<p>
    Opravdu chcete obnovit strom č.
    <strong>
        <?= htmlspecialchars(
            (string) $tree['usr_id'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </strong>

    <?php if (!empty($tree['species'])): ?>
        –
        <em>
            <?= htmlspecialchars(
                (string) $tree['species'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </em>
    <?php endif; ?>
    ?
</p>

<p>
    Strom bude znovu veden jako aktivní.
    Údaje o odstranění budou vymazány.
</p>

<?php if ($error !== ''): ?>

    <div class="admin-message admin-message--error">
        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

<?php endif; ?>

<form
    method="post"
    class="admin-form"
>
<input type="hidden" name="tree_form_token" value="<?= htmlspecialchars($_SESSION['tree_form_csrf'],ENT_QUOTES,'UTF-8') ?>">

    <input
        type="hidden"
        name="id"
        value="<?= (int) $treeId ?>"
    >

    <div class="admin-actions">

        <button
            type="submit"
            class="btn"
        >
            Obnovit strom
        </button>

        <a
            href="/map/public/"
            class="btn btn-secondary"
        >
            Zrušit
        </a>

    </div>

</form>

<?php

require_once dirname(__DIR__, 2)
    . '/app/includes/footer.php';

?>