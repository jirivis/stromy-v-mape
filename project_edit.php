<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$userId =
    (int) ($_SESSION['user_id'] ?? 0);

$role =
    $_SESSION['role']
    ?? 'viewer';

$isSuperAdmin =
    (int) (
        $_SESSION['is_super_admin']
        ?? 0
    );

$projectId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$projectId || $projectId < 1) {
    http_response_code(400);
    die('Chybí platné ID projektu.');
}


/*
---------------------------------------------------
 Načtení projektu a oprávnění
---------------------------------------------------
*/

$stmt = $mysqli->prepare(
    'SELECT
        p.id,
        p.name,
        p.client_id,
        p.owner_id,
        p.visibility,
        p.created_at,

        CASE
            WHEN p.owner_id = ?
                THEN "owner"
            ELSE pu.project_role
        END AS project_role

     FROM projects p

     LEFT JOIN project_users pu
        ON pu.project_id = p.id
        AND pu.user_id = ?

     WHERE p.id = ?

     LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    die('Projekt se nepodařilo načíst.');
}

$stmt->bind_param(
    'iii',
    $userId,
    $userId,
    $projectId
);

$stmt->execute();

$result =
    $stmt->get_result();

$project =
    $result->fetch_assoc();

$stmt->close();

if (!$project) {
    http_response_code(404);
    die('Projekt nebyl nalezen.');
}


/*
 * Upravovat projekt může:
 * - superadmin,
 * - vlastník projektu,
 * - projektový admin.
 */
$projectRole =
    (string) (
        $project['project_role']
        ?? ''
    );

$globalRole =
    $_SESSION['role']
    ?? 'viewer';

$canEditProject =
    $isSuperAdmin === 1
    || $globalRole === 'admin'
    || in_array(
        $projectRole,
        [
            'owner',
            'admin'
        ],
        true
    );

if (!$canEditProject) {
    http_response_code(403);

    die(
        'Nemáte oprávnění upravovat tento projekt.'
    );
}

$canChangeOwner =
    $isSuperAdmin === 1
    || $globalRole === 'admin';


/*
---------------------------------------------------
 Zákazníci
---------------------------------------------------
*/

$clients = [];

$clientResult =
    $mysqli->query(
        'SELECT
            id,
            name,
            ico
         FROM clients
         ORDER BY
            name,
            id'
    );

if ($clientResult) {

    while (
        $client =
            $clientResult->fetch_assoc()
    ) {
        $clients[] = $client;
    }
}


/*
---------------------------------------------------
 Aktivní uživatelé
---------------------------------------------------
*/

$users = [];

$userResult =
    $mysqli->query(
        'SELECT
            id,
            username,
            email
         FROM users
         WHERE active = 1
         ORDER BY
            username,
            id'
    );

if ($userResult) {

    while (
        $user =
            $userResult->fetch_assoc()
    ) {
        $users[] = $user;
    }
}


/*
---------------------------------------------------
 Výchozí hodnoty
---------------------------------------------------
*/

$name =
    (string) $project['name'];

$clientId =
    (int) ($project['client_id'] ?? 0);

$ownerId =
    (int) $project['owner_id'];

$visibility =
    (string) $project['visibility'];

$error = '';
$success = '';


/*
---------------------------------------------------
 Uložení změn
---------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $name =
        trim(
            $_POST['name']
            ?? ''
        );

    $clientId =
        (int) (
            $_POST['client_id']
            ?? 0
        );

if ($canChangeOwner) {

    $ownerId =
        (int) (
            $_POST['owner_id']
            ?? $project['owner_id']
        );

} else {

    $ownerId =
        (int) $project['owner_id'];
}

    $visibility =
        $_POST['visibility']
        ?? 'private';


    if ($name === '') {

        $error =
            'Zadejte název projektu.';
    }


    if (
        !in_array(
            $visibility,
            [
                'private',
                'public'
            ],
            true
        )
    ) {
        $visibility =
            'private';
    }


    /*
     * Ověření zákazníka.
     */
    if (
        $error === ''
        && $clientId > 0
    ) {

        $clientStmt =
            $mysqli->prepare(
                'SELECT id
                 FROM clients
                 WHERE id = ?
                 LIMIT 1'
            );

        if (!$clientStmt) {

            $error =
                'Zadavatele se nepodařilo ověřit.';

        } else {

            $clientStmt->bind_param(
                'i',
                $clientId
            );

            $clientStmt->execute();

            $clientCheck =
                $clientStmt
                    ->get_result()
                    ->fetch_assoc();

            $clientStmt->close();

            if (!$clientCheck) {

                $error =
                    'Vybraný zadavatel neexistuje.';
            }
        }
    }


    /*
     * Ověření vlastníka.
     */
    if ($error === '') {

        $ownerStmt =
            $mysqli->prepare(
                'SELECT id
                 FROM users
                 WHERE id = ?
                   AND active = 1
                 LIMIT 1'
            );

        if (!$ownerStmt) {

            $error =
                'Vlastníka projektu se nepodařilo ověřit.';

        } else {

            $ownerStmt->bind_param(
                'i',
                $ownerId
            );

            $ownerStmt->execute();

            $ownerCheck =
                $ownerStmt
                    ->get_result()
                    ->fetch_assoc();

            $ownerStmt->close();

            if (!$ownerCheck) {

                $error =
                    'Vybraný vlastník neexistuje '
                    . 'nebo není aktivní.';
            }
        }
    }


    if ($error === '') {

        $clientIdForDb =
            $clientId > 0
                ? $clientId
                : null;

        $mysqli->begin_transaction();

        try {

            /*
             * Nejprve zjistíme původního vlastníka.
             */
            $oldOwnerId =
                (int) $project['owner_id'];


            /*
             * Aktualizace projektu.
             */
            $updateStmt =
                $mysqli->prepare(
                    'UPDATE projects
                     SET
                        name = ?,
                        client_id = ?,
                        owner_id = ?,
                        visibility = ?
                     WHERE id = ?
                     LIMIT 1'
                );

            if (!$updateStmt) {
                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $updateStmt->bind_param(
                'siisi',
                $name,
                $clientIdForDb,
                $ownerId,
                $visibility,
                $projectId
            );

            if (!$updateStmt->execute()) {
                throw new RuntimeException(
                    $updateStmt->error
                );
            }

            $updateStmt->close();


            /*
             * Nový vlastník musí být
             * v project_users jako owner.
             */
            $newOwnerStmt =
                $mysqli->prepare(
                    'INSERT INTO project_users (
                        project_id,
                        user_id,
                        project_role
                     )
                     VALUES (?, ?, "owner")
                     ON DUPLICATE KEY UPDATE
                        project_role = "owner"'
                );

            if (!$newOwnerStmt) {
                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $newOwnerStmt->bind_param(
                'ii',
                $projectId,
                $ownerId
            );

            if (!$newOwnerStmt->execute()) {
                throw new RuntimeException(
                    $newOwnerStmt->error
                );
            }

            $newOwnerStmt->close();


            /*
             * Pokud se vlastník změnil,
             * starý vlastník už nemá mít roli owner.
             * Přepneme ho na admin.
             */
            if (
                $oldOwnerId > 0
                && $oldOwnerId !== $ownerId
            ) {

                $oldOwnerStmt =
                    $mysqli->prepare(
                        'UPDATE project_users
                         SET project_role = "admin"
                         WHERE project_id = ?
                           AND user_id = ?
                           AND project_role = "owner"'
                    );

                if ($oldOwnerStmt) {

                    $oldOwnerStmt->bind_param(
                        'ii',
                        $projectId,
                        $oldOwnerId
                    );

                    $oldOwnerStmt->execute();
                    $oldOwnerStmt->close();
                }
            }


            $mysqli->commit();

            /*
             * Pokud právě upravujeme aktivní projekt,
             * aktualizujeme název v session.
             */
            if (
                (int) (
                    $_SESSION['active_project_id']
                    ?? 0
                ) === $projectId
            ) {

                $_SESSION['active_project_name'] =
                    $name;

                if ($isSuperAdmin === 1) {

                    $_SESSION['active_project_role'] =
                        'admin';

                } elseif ($ownerId === $userId) {

                    $_SESSION['active_project_role'] =
                        'owner';
                }
            }

            $project['owner_id'] =
                $ownerId;

            $success =
                'Projekt byl úspěšně upraven.';

        } catch (Throwable $exception) {

            $mysqli->rollback();

            error_log(
                'project_edit.php: '
                . $exception->getMessage()
            );

            $error =
                'Projekt se nepodařilo uložit.';
        }
    }
}


/*
---------------------------------------------------
 Vzhled
---------------------------------------------------
*/

$pageTitle =
    'Upravit projekt';

$pageSubtitle =
    'Správa projektů';

$cardClass =
    'admin-card admin-card--small';

require_once __DIR__
    . '/app/includes/header.php';

?>


<?php if ($error !== ''): ?>

    <div
        class="admin-message admin-message--error"
    >
        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

<?php endif; ?>


<?php if ($success !== ''): ?>

    <div
        class="admin-message admin-message--success"
    >
        <?= htmlspecialchars(
            $success,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

<?php endif; ?>


<h2>
    Upravit projekt
</h2>


<form
    method="post"
    class="admin-form"
>

    <div class="admin-field">

        <label for="name">
            Název projektu
        </label>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            required
            value="<?= htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

    </div>


    <div class="admin-field">

        <label for="client_id">
            Zadavatel
        </label>

        <select
            id="client_id"
            name="client_id"
        >

            <option value="0">
                — bez zadavatele —
            </option>

            <?php foreach (
                $clients
                as $client
            ): ?>

                <option
                    value="<?= (int) $client['id'] ?>"
                    <?= (
                        (int) $client['id']
                        === $clientId
                    )
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= htmlspecialchars(
                        $client['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <?php if (!empty($client['ico'])): ?>

                        – IČO
                        <?= htmlspecialchars(
                            $client['ico'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    <?php endif; ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <div class="admin-field">

        <label for="visibility">
            Viditelnost
        </label>

        <select
            id="visibility"
            name="visibility"
        >

            <option
                value="private"
                <?= $visibility === 'private'
                    ? 'selected'
                    : ''
                ?>
            >
                Soukromý
            </option>

            <option
                value="public"
                <?= $visibility === 'public'
                    ? 'selected'
                    : ''
                ?>
            >
                Veřejný
            </option>

        </select>

    </div>


    <div class="admin-field">

        <label for="owner_id">
            Vlastník projektu
        </label>

        <select
            id="owner_id"
            name="owner_id"
            required
        >

            <?php foreach (
                $users
                as $user
            ): ?>

                <option
                    value="<?= (int) $user['id'] ?>"
                    <?= (
                        (int) $user['id']
                        === $ownerId
                    )
                        ? 'selected'
                        : ''
                    ?>
                >
                    <?= htmlspecialchars(
                        $user['username'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                    <?php if (!empty($user['email'])): ?>

                        –
                        <?= htmlspecialchars(
                            $user['email'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    <?php endif; ?>

                </option>

            <?php endforeach; ?>

        </select>

    </div>


    <div class="admin-actions">

        <button
            type="submit"
            class="btn"
        >
            Uložit změny
        </button>

        <a
            href="/projects.php"
            class="btn btn-secondary"
        >
            Zpět
        </a>

    </div>

</form>


<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>