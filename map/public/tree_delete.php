<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../auth_api.php';
require_once __DIR__ . '/../../config_app.php';
require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');
$_SESSION['tree_form_csrf']??=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&!hash_equals($_SESSION['tree_form_csrf'],(string)($_POST['tree_form_token']??''))){http_response_code(403);exit('Platnost formuláře vypršela. Obnovte stránku.');}


$mysqli->set_charset('utf8mb4');


/*
---------------------------------------------------
 ID stromu
---------------------------------------------------
*/

$treeId =
    (int) (
        $_GET['id']
        ?? $_POST['id']
        ?? 0
    );

if ($treeId <= 0) {

    http_response_code(400);

    die(
        'Chybí ID stromu.'
    );
}


/*
---------------------------------------------------
 Aktivní projekt
---------------------------------------------------
*/

$activeProjectId =
    (int) (
        $_SESSION['active_project_id']
        ?? 0
    );

if ($activeProjectId <= 0) {

    http_response_code(403);

    die(
        'Není vybrán aktivní projekt.'
    );
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
        'Nemáte oprávnění trvale mazat stromy.'
    );
}


/*
---------------------------------------------------
 Načtení stromu

 Strom MUSÍ patřit do aktivního projektu.
---------------------------------------------------
*/

$stmt =
    $mysqli->prepare(
        'SELECT
            id,
            project_id,
            usr_id,
            species,
            czech,
            status
         FROM trees
         WHERE id = ?
           AND project_id = ?
         LIMIT 1'
    );

if (!$stmt) {

    http_response_code(500);

    die(
        'Strom se nepodařilo načíst.'
    );
}

$stmt->bind_param(
    'ii',
    $treeId,
    $activeProjectId
);

$stmt->execute();

$tree =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();


if (!$tree) {

    http_response_code(404);

    die(
        'Strom nebyl nalezen v aktivním projektu.'
    );
}


/*
---------------------------------------------------
 Počty souvisejících záznamů
---------------------------------------------------
*/

$historyCount = 0;
$photoCount = 0;
$historyPhotoCount = 0;


/*
 * Historické snapshoty.
 */
$countHistoryStmt =
    $mysqli->prepare(
        'SELECT COUNT(*) AS cnt
         FROM tree_history
         WHERE tree_id = ?
           AND project_id = ?'
    );

if ($countHistoryStmt) {

    $countHistoryStmt->bind_param(
        'ii',
        $treeId,
        $activeProjectId
    );

    $countHistoryStmt->execute();

    $row =
        $countHistoryStmt
            ->get_result()
            ->fetch_assoc();

    $historyCount =
        (int) (
            $row['cnt']
            ?? 0
        );

    $countHistoryStmt->close();
}


/*
 * Fotografie stromu.
 */
$countPhotosStmt =
    $mysqli->prepare(
        'SELECT COUNT(*) AS cnt
         FROM trees_photo
         WHERE tree_id = ?'
    );

if ($countPhotosStmt) {

    $countPhotosStmt->bind_param(
        'i',
        $treeId
    );

    $countPhotosStmt->execute();

    $row =
        $countPhotosStmt
            ->get_result()
            ->fetch_assoc();

    $photoCount =
        (int) (
            $row['cnt']
            ?? 0
        );

    $countPhotosStmt->close();
}


/*
 * Vazby fotografií na historické snapshoty.
 */
$countHistoryPhotosStmt =
    $mysqli->prepare(
        'SELECT COUNT(*) AS cnt
         FROM tree_history_photos thp
         LEFT JOIN tree_history h
            ON h.id = thp.history_id
         LEFT JOIN trees_photo p
            ON p.id = thp.photo_id
         WHERE
            h.tree_id = ?
            OR p.tree_id = ?'
    );

if ($countHistoryPhotosStmt) {

    $countHistoryPhotosStmt->bind_param(
        'ii',
        $treeId,
        $treeId
    );

    $countHistoryPhotosStmt->execute();

    $row =
        $countHistoryPhotosStmt
            ->get_result()
            ->fetch_assoc();

    $historyPhotoCount =
        (int) (
            $row['cnt']
            ?? 0
        );

    $countHistoryPhotosStmt->close();
}


/*
---------------------------------------------------
 Smazání
---------------------------------------------------
*/

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $confirmUsrId =
        trim(
            $_POST['confirm_usr_id']
            ?? ''
        );

    $confirmed =
        isset(
            $_POST['confirm_delete']
        )
        && $_POST['confirm_delete'] === '1';


    /*
     * Musí souhlasit uživatelské číslo stromu.
     */
    if (
        $confirmUsrId
        !== (string) $tree['usr_id']
    ) {

        $error =
            'Zadané číslo stromu nesouhlasí.';

    } elseif (!$confirmed) {

        $error =
            'Musíte potvrdit, že rozumíte nevratnosti této akce.';
    }


    if ($error === '') {

        try {

            /*
             * Vše smažeme jako jednu transakci.
             *
             * Pokud některý krok selže,
             * vrátíme všechny předchozí změny.
             */
            $mysqli->begin_transaction();


            /*
             * 1. Vazby historických fotografií.
             *
             * Mažeme vazby patřící buď:
             * - historickým snapshotům stromu
             * - fotografiím stromu
             */
            $deleteHistoryPhotosStmt =
                $mysqli->prepare(
                    'DELETE thp
                     FROM tree_history_photos thp

                     LEFT JOIN tree_history h
                        ON h.id = thp.history_id

                     LEFT JOIN trees_photo p
                        ON p.id = thp.photo_id

                     WHERE
                        h.tree_id = ?
                        OR p.tree_id = ?'
                );

            if (!$deleteHistoryPhotosStmt) {

                throw new RuntimeException(
                    'Nelze připravit odstranění historických fotografií.'
                );
            }

            $deleteHistoryPhotosStmt
                ->bind_param(
                    'ii',
                    $treeId,
                    $treeId
                );

            if (
                !$deleteHistoryPhotosStmt
                    ->execute()
            ) {

                throw new RuntimeException(
                    'Nepodařilo se odstranit vazby historických fotografií.'
                );
            }

            $deleteHistoryPhotosStmt
                ->close();


            /*
             * 2. Historické snapshoty.
             */
            $deleteHistoryStmt =
                $mysqli->prepare(
                    'DELETE FROM tree_history
                     WHERE tree_id = ?
                       AND project_id = ?'
                );

            if (!$deleteHistoryStmt) {

                throw new RuntimeException(
                    'Nelze připravit odstranění historie stromu.'
                );
            }

            $deleteHistoryStmt
                ->bind_param(
                    'ii',
                    $treeId,
                    $activeProjectId
                );

            if (!$deleteHistoryStmt->execute()) {

                throw new RuntimeException(
                    'Nepodařilo se odstranit historii stromu.'
                );
            }

            $deleteHistoryStmt->close();


            /*
             * 3. Fotografie stromu.
             *
             * Zatím mažeme databázové záznamy.
             * Samotné obrazové soubory na disku
             * budeme řešit samostatně až podle
             * skutečného umístění uploadů.
             */
            $deletePhotosStmt =
                $mysqli->prepare(
                    'DELETE FROM trees_photo
                     WHERE tree_id = ?'
                );

            if (!$deletePhotosStmt) {

                throw new RuntimeException(
                    'Nelze připravit odstranění fotografií.'
                );
            }

            $deletePhotosStmt->bind_param(
                'i',
                $treeId
            );

            if (!$deletePhotosStmt->execute()) {

                throw new RuntimeException(
                    'Nepodařilo se odstranit fotografie stromu.'
                );
            }

            $deletePhotosStmt->close();


            /*
             * 4. Nakonec samotný strom.
             *
             * Znovu kontrolujeme project_id.
             */
            $deleteTreeStmt =
                $mysqli->prepare(
                    'DELETE FROM trees
                     WHERE id = ?
                       AND project_id = ?
                     LIMIT 1'
                );

            if (!$deleteTreeStmt) {

                throw new RuntimeException(
                    'Nelze připravit odstranění stromu.'
                );
            }

            $deleteTreeStmt->bind_param(
                'ii',
                $treeId,
                $activeProjectId
            );

            if (!$deleteTreeStmt->execute()) {

                throw new RuntimeException(
                    'Strom se nepodařilo odstranit.'
                );
            }

            if (
                $deleteTreeStmt->affected_rows
                !== 1
            ) {

                throw new RuntimeException(
                    'Strom nebyl odstraněn.'
                );
            }

            $deleteTreeStmt->close();


            /*
             * Vše proběhlo správně.
             */
            $mysqli->commit();

            ?>
            <!DOCTYPE html>

            <html lang="cs">

            <head>

                <meta charset="UTF-8">

                <title>
                    Strom byl smazán
                </title>

            </head>

            <body>

            <script>

                if (
                    window.parent
                    && window.parent !== window
                ) {

                    window.parent.postMessage(
                        {
                            type: "tree-deleted",
                            treeId: <?= (int) $treeId ?>
                        },
                        window.location.origin
                    );

                    window.parent.location.reload();

                } else {

                    window.location.replace(
                        "/map/public/"
                    );
                }

            </script>

            </body>

            </html>

            <?php

            exit;

        } catch (Throwable $e) {

            $mysqli->rollback();

            error_log(
                'tree_delete.php: '
                . $e->getMessage()
            );

            $error =
                'Strom se nepodařilo trvale smazat. '
                . 'Databáze zůstala beze změny.';
        }
    }
}


/*
---------------------------------------------------
 Vzhled
---------------------------------------------------
*/

$pageTitle =
    'Trvalé smazání stromu';

$pageSubtitle =
    'Nebezpečná akce';

$cardClass =
    'admin-card admin-card--small';

require_once dirname(__DIR__, 2)
    . '/app/includes/header.php';

?>


<h2>
    Trvale smazat strom
</h2>


<div class="admin-message admin-message--error">

    <strong>
        Tato akce je nevratná.
    </strong>

    <br>

    Strom, jeho historie a vazby na fotografie
    budou trvale odstraněny z databáze.

</div>


<p>
    Strom č.
    <strong>
        <?= htmlspecialchars(
            (string) $tree['usr_id'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </strong>

    <?php if (
        !empty($tree['species'])
    ): ?>

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


<h3>
    Co bude odstraněno
</h3>

<p>
    Historické záznamy:
    <strong>
        <?= $historyCount ?>
    </strong>
</p>

<p>
    Fotografie stromu:
    <strong>
        <?= $photoCount ?>
    </strong>
</p>

<p>
    Historické vazby fotografií:
    <strong>
        <?= $historyPhotoCount ?>
    </strong>
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


    <div class="admin-field">

        <label for="confirm_usr_id">

            Pro potvrzení zadejte číslo stromu
            <strong>
                <?= htmlspecialchars(
                    (string) $tree['usr_id'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>

        </label>

        <input
            type="text"
            id="confirm_usr_id"
            name="confirm_usr_id"
            autocomplete="off"
            required
        >

    </div>


    <div class="admin-field">

        <label>

            <input
                type="checkbox"
                name="confirm_delete"
                value="1"
                required
            >

            Rozumím, že toto smazání
            nelze vrátit zpět.

        </label>

    </div>


    <div class="admin-actions">

        <button
            type="submit"
            class="btn tree-delete-permanent-btn"
        >
            Trvale smazat strom
        </button>

        <a
            href="tree_new.php?id=<?= (int) $treeId ?>"
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