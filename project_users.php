<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__.'/app/auth_form.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$userId =
    (int) ($_SESSION['user_id'] ?? 0);
	
$globalRole =
    $_SESSION['role']
    ?? 'viewer';	

$isSuperAdmin =
    (int) ($_SESSION['is_super_admin'] ?? 0);

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
        p.owner_id,

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

$stmt->bind_param(
    'iii',
    $userId,
    $userId,
    $projectId
);

$stmt->execute();

$project =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$project) {
    http_response_code(404);
    die('Projekt nebyl nalezen.');
}

$currentProjectRole =
    (string) (
        $project['project_role']
        ?? ''
    );

$canManageUsers =
    $isSuperAdmin === 1
    || in_array(
        $currentProjectRole,
        [
            'owner',
            'admin'
        ],
        true
    );

if (!$canManageUsers) {
    http_response_code(403);

    die(
        'Nemáte oprávnění spravovat '
        . 'uživatele tohoto projektu.'
    );
}


$error = '';
$success = '';


/*
---------------------------------------------------
 Zpracování akcí
---------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $action =
        $_POST['action']
        ?? '';

    $targetUserId =
        (int) (
            $_POST['user_id']
            ?? 0
        );


    /*
    -----------------------------------------------
     Přidání uživatele
    -----------------------------------------------
    */

    if ($action === 'add') {

        $projectRole =
            $_POST['project_role']
            ?? 'viewer';

        if (
            !in_array(
                $projectRole,
                [
                    'admin',
                    'editor',
                    'viewer'
                ],
                true
            )
        ) {
            $projectRole = 'viewer';
        }

        if ($targetUserId < 1) {

            $error =
                'Vyberte uživatele.';

        } elseif (
            $targetUserId
            === (int) $project['owner_id']
        ) {

            $error =
                'Vlastník projektu už má '
                . 'roli owner.';

        } else {

            $userCheckStmt =
                $mysqli->prepare(
                    'SELECT id
                     FROM users
                     WHERE id = ?
                       AND active = 1
                     LIMIT 1'
                );

            $userCheckStmt->bind_param(
                'i',
                $targetUserId
            );

            $userCheckStmt->execute();

            $userExists =
                $userCheckStmt
                    ->get_result()
                    ->fetch_assoc();

            $userCheckStmt->close();

            if (!$userExists) {

                $error =
                    'Vybraný uživatel '
                    . 'neexistuje nebo není aktivní.';

            } else {

                $addStmt =
                    $mysqli->prepare(
                        'INSERT INTO project_users (
                            project_id,
                            user_id,
                            project_role
                         )
                         VALUES (?, ?, ?)

                         ON DUPLICATE KEY UPDATE
                            project_role =
                                VALUES(project_role)'
                    );

                $addStmt->bind_param(
                    'iis',
                    $projectId,
                    $targetUserId,
                    $projectRole
                );

                if ($addStmt->execute()) {

                    $success =
                        'Uživatel byl přidán '
                        . 'do projektu.';

                } else {

                    $error =
                        'Uživatele se nepodařilo '
                        . 'přidat.';
                }

                $addStmt->close();
            }
        }
    }


    /*
    -----------------------------------------------
     Změna role
    -----------------------------------------------
    */

    if ($action === 'change_role') {

        $projectRole =
            $_POST['project_role']
            ?? 'viewer';

        if (
            !in_array(
                $projectRole,
                [
                    'admin',
                    'editor',
                    'viewer'
                ],
                true
            )
        ) {
            $error =
                'Neplatná role.';
        }

        if (
            $error === ''
            && $targetUserId
                === (int) $project['owner_id']
        ) {

            $error =
                'Roli vlastníka projektu '
                . 'nelze změnit zde.';
        }

        if (
            $error === ''
            && $targetUserId > 0
        ) {

            $roleStmt =
                $mysqli->prepare(
                    'UPDATE project_users
                     SET project_role = ?
                     WHERE project_id = ?
                       AND user_id = ?'
                );

            $roleStmt->bind_param(
                'sii',
                $projectRole,
                $projectId,
                $targetUserId
            );

            if ($roleStmt->execute()) {

                $success =
                    'Role uživatele byla změněna.';

            } else {

                $error =
                    'Roli se nepodařilo změnit.';
            }

            $roleStmt->close();
        }
    }


    /*
    -----------------------------------------------
     Odebrání uživatele
    -----------------------------------------------
    */

    if ($action === 'remove') {

        if (
            $targetUserId
            === (int) $project['owner_id']
        ) {

            $error =
                'Vlastníka projektu nelze odebrat.';

        } elseif ($targetUserId < 1) {

            $error =
                'Neplatný uživatel.';

        } else {

            $removeStmt =
                $mysqli->prepare(
                    'DELETE FROM project_users
                     WHERE project_id = ?
                       AND user_id = ?'
                );

            $removeStmt->bind_param(
                'ii',
                $projectId,
                $targetUserId
            );

            if ($removeStmt->execute()) {

                $success =
                    'Uživatel byl z projektu odebrán.';

            } else {

                $error =
                    'Uživatele se nepodařilo odebrat.';
            }

            $removeStmt->close();
        }
    }
}


/*
---------------------------------------------------
 Uživatelé projektu
---------------------------------------------------
*/

$projectUsers = [];

$usersStmt =
    $mysqli->prepare(
        'SELECT
            u.id,
            u.username,
            u.email,
            u.role AS global_role,
            u.active,

            CASE
                WHEN u.id = p.owner_id
                    THEN "owner"
                ELSE pu.project_role
            END AS project_role

         FROM projects p

         JOIN users u
            ON (
                u.id = p.owner_id
                OR u.id IN (
                    SELECT user_id
                    FROM project_users
                    WHERE project_id = p.id
                )
            )

         LEFT JOIN project_users pu
            ON pu.project_id = p.id
            AND pu.user_id = u.id

         WHERE p.id = ?

         ORDER BY
            CASE
                WHEN u.id = p.owner_id
                    THEN 0
                ELSE 1
            END,
            u.username'
    );

$usersStmt->bind_param(
    'i',
    $projectId
);

$usersStmt->execute();

$usersResult =
    $usersStmt->get_result();

while (
    $row =
        $usersResult->fetch_assoc()
) {
    $projectUsers[] = $row;
}

$usersStmt->close();


/*
---------------------------------------------------
 Uživatelé, které lze přidat
---------------------------------------------------
*/

$availableUsers = [];

$availableStmt =
    $mysqli->prepare(
        'SELECT
            u.id,
            u.username,
            u.email

         FROM users u

         WHERE
            u.active = 1

            AND u.id <> ?

            AND NOT EXISTS (
                SELECT 1
                FROM project_users pu
                WHERE pu.project_id = ?
                  AND pu.user_id = u.id
            )

         ORDER BY
            u.username,
            u.id'
    );

$ownerId =
    (int) $project['owner_id'];

$availableStmt->bind_param(
    'ii',
    $ownerId,
    $projectId
);

$availableStmt->execute();

$availableResult =
    $availableStmt->get_result();

while (
    $row =
        $availableResult->fetch_assoc()
) {
    $availableUsers[] = $row;
}

$availableStmt->close();


$roleLabels = [
    'owner' =>
        'Vlastník',

    'admin' =>
        'Administrátor',

    'editor' =>
        'Editor',

    'viewer' =>
        'Prohlížení'
];


$pageTitle =
    'Uživatelé projektu';

$pageSubtitle =
    (string) $project['name'];

$cardClass =
    'admin-card admin-card--wide';

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


<div class="admin-projects-toolbar">

    <div>

        <h2>
            Uživatelé projektu
        </h2>

        <p>
            <?= htmlspecialchars(
                $project['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </p>

    </div>
<div class="admin-toolbar-actions"><a class="btn btn-secondary" href="/dashboard.php">← Dashboard</a>

</div>
</div>

<?php if ($availableUsers): ?>

    <section class="admin-section">

        <h3>
            Přidat uživatele
        </h3>

        <form
            method="post"
            class="admin-form"
        >
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

            <input
                type="hidden"
                name="action"
                value="add"
            >

            <div class="admin-field">

                <label for="user_id">
                    Uživatel
                </label>

                <select
                    id="user_id"
                    name="user_id"
                    required
                >

                    <option value="">
                        — vyberte uživatele —
                    </option>

                    <?php foreach (
                        $availableUsers
                        as $user
                    ): ?>

                        <option
                            value="<?= (int) $user['id'] ?>"
                        >
                            <?= htmlspecialchars(
                                $user['username'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                            <?php if (
                                !empty($user['email'])
                            ): ?>

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


            <div class="admin-field">

                <label for="project_role">
                    Role v projektu
                </label>

                <select
                    id="project_role"
                    name="project_role"
                >

                    <option value="viewer">
                        Prohlížení
                    </option>

                    <option value="editor">
                        Editor
                    </option>

                    <option value="admin">
                        Administrátor
                    </option>

                </select>

            </div>


            <div class="admin-actions">

                <button
                    type="submit"
                    class="btn"
                >
                    Přidat uživatele
                </button>

            </div>

        </form>

    </section>

<?php endif; ?>


<section class="admin-section">

    <h3>
        Přístupy k projektu
    </h3>

    <?php if (!$projectUsers): ?>

        <p>
            K projektu nejsou přiřazeni uživatelé.
        </p>

    <?php else: ?>

        <div class="project-user-list">

            <?php foreach (
                $projectUsers
                as $projectUser
            ): ?>

                <?php

                $targetId =
                    (int) $projectUser['id'];

                $targetRole =
                    (string) (
                        $projectUser['project_role']
                        ?? 'viewer'
                    );

                $isOwner =
                    $targetRole === 'owner';

                ?>

                <div class="project-user-row">

                    <div class="project-user-row__identity">

                        <strong>
                            <?= htmlspecialchars(
                                $projectUser['username'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </strong>

                        <?php if (
                            !empty(
                                $projectUser['email']
                            )
                        ): ?>

                            <span>
                                <?= htmlspecialchars(
                                    $projectUser['email'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        <?php endif; ?>

                    </div>


                    <?php if ($isOwner): ?>

                        <div
                            class="project-user-role project-user-role--owner"
                        >
                            Vlastník
                        </div>

                    <?php else: ?>

                        <form
                            method="post"
                            class="project-user-role-form"
                        >
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="change_role"
                            >

                            <input
                                type="hidden"
                                name="user_id"
                                value="<?= $targetId ?>"
                            >

                            <select
                                name="project_role"
                                onchange="this.form.submit()"
                            >

                                <?php foreach (
                                    [
                                        'viewer',
                                        'editor',
                                        'admin'
                                    ]
                                    as $roleValue
                                ): ?>

                                    <option
                                        value="<?= $roleValue ?>"
                                        <?= $targetRole === $roleValue
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= htmlspecialchars(
                                            $roleLabels[$roleValue],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </form>


                        <form
                            method="post"
                            onsubmit="
                                return confirm(
                                    'Opravdu odebrat tohoto uživatele z projektu?'
                                );
                            "
                        >
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

                            <input
                                type="hidden"
                                name="action"
                                value="remove"
                            >

                            <input
                                type="hidden"
                                name="user_id"
                                value="<?= $targetId ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-secondary"
                            >
                                Odebrat
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<div class="admin-actions">

    <a
        href="/projects.php"
        class="btn btn-secondary"
    >
        Zpět na projekty
    </a>

</div>


<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>