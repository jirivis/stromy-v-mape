<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../config_app.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/tree_history.php';

header('Content-Type: text/html; charset=utf-8');

$mysqli->set_charset('utf8mb4');

$treeId =
    (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($treeId <= 0) {
    http_response_code(400);
    die('Chybí ID stromu.');
}


/*
 * Načtení stromu.
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
    die('Nepodařilo se připravit dotaz.');
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
 * Bezpečnost:
 * strom musí patřit do právě aktivního projektu.
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
 * Odstranit strom může owner/admin/editor.
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
        'Nemáte oprávnění označit strom '
        . 'jako odstraněný.'
    );
}


$error = '';

$removedAt =
    $tree['removed_at']
    ?: date('Y-m-d');

$removalType =
    (string) (
        $tree['removal_type']
        ?? ''
    );

$removalReason =
    (string) (
        $tree['removal_reason']
        ?? ''
    );


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $removedAt =
        trim(
            $_POST['removed_at']
            ?? ''
        );

    $removalType =
        $_POST['removal_type']
        ?? '';

    $removalReason =
        trim(
            $_POST['removal_reason']
            ?? ''
        );


    $allowedTypes = [
        'felled',
        'dead',
        'fallen',
        'missing',
        'other'
    ];


    if ($removedAt === '') {

        $error =
            'Zadejte datum odstranění.';

    } elseif (
        !in_array(
            $removalType,
            $allowedTypes,
            true
        )
    ) {

        $error =
            'Vyberte způsob odstranění.';
    }


    if ($error === '') {

        $updateStmt =
            $mysqli->prepare(
                'UPDATE trees
                 SET
                    status = "removed",
                    removed_at = ?,
                    removal_type = ?,
                    removal_reason = ?
                 WHERE id = ?
                   AND project_id = ?'
            );

        if (!$updateStmt) {

            $error =
                'Změnu se nepodařilo připravit.';

        } else {

            $updateStmt->bind_param(
                'sssii',
                $removedAt,
                $removalType,
                $removalReason,
                $treeId,
                $activeProjectId
            );

if ($updateStmt->execute()) {

    $updateStmt->close();
	
	/*
 * Automatický historický snapshot
 * po označení stromu jako odstraněného.
 */
$userId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );

$historyNote =
    'Strom označen jako odstraněný.';

if ($removalReason !== '') {
    $historyNote .=
        ' Důvod: '
        . $removalReason;
}

try {

    createTreeHistorySnapshot(
        $mysqli,
        $treeId,
        $userId,
        'removed',
        $historyNote
    );

} catch (Throwable $e) {

    error_log(
        'tree_remove.php – history snapshot: '
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
                    'Strom se nepodařilo označit '
                    . 'jako odstraněný.';

                $updateStmt->close();
            }
        }
    }
}

$pageTitle =
    'Odstranění stromu';

$pageSubtitle =
    'Správa stromu';

$cardClass =
    'admin-card admin-card--small';

require_once dirname(__DIR__, 2)
    . '/app/includes/header.php';

?>

<h2>Odstranění stromu</h2>

<p>
    Strom č.
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

    <input
        type="hidden"
        name="id"
        value="<?= (int) $treeId ?>"
    >


    <div class="admin-field">

        <label for="removed_at">
            Datum odstranění
        </label>

        <input
            type="date"
            id="removed_at"
            name="removed_at"
            required
            value="<?= htmlspecialchars(
                $removedAt,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="removal_type">
            Způsob / stav
        </label>

        <select
            id="removal_type"
            name="removal_type"
            required
        >

            <option value="">
                — vyberte —
            </option>

            <option
                value="felled"
                <?= $removalType === 'felled'
                    ? 'selected'
                    : '' ?>
            >
                Pokácen
            </option>

            <option
                value="dead"
                <?= $removalType === 'dead'
                    ? 'selected'
                    : '' ?>
            >
                Odumřel
            </option>

            <option
                value="fallen"
                <?= $removalType === 'fallen'
                    ? 'selected'
                    : '' ?>
            >
                Vyvrácen / zlomen
            </option>

            <option
                value="missing"
                <?= $removalType === 'missing'
                    ? 'selected'
                    : '' ?>
            >
                Nenalezen
            </option>

            <option
                value="other"
                <?= $removalType === 'other'
                    ? 'selected'
                    : '' ?>
            >
                Jiný důvod
            </option>

        </select>

    </div>


    <div class="admin-field">

        <label for="removal_reason">
            Důvod odstranění / poznámka
        </label>

        <textarea
            id="removal_reason"
            name="removal_reason"
            rows="6"
        ><?= htmlspecialchars(
            $removalReason,
            ENT_QUOTES,
            'UTF-8'
        ) ?></textarea>

    </div>


    <div class="admin-actions">

        <button
            type="submit"
            class="btn"
        >
            Označit strom jako odstraněný
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