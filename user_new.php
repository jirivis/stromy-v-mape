<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';



require_once __DIR__ . '/auth.php';
require_once __DIR__.'/app/auth_form.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';
require_once __DIR__ . '/app/mail/send_mail.php';

$currentUserId =
    (int) ($_SESSION['user_id'] ?? 0);

$currentRole =
    $_SESSION['role']
    ?? 'viewer';

$isSuperAdmin =
    (int) (
        $_SESSION['is_super_admin']
        ?? 0
    );

if ($isSuperAdmin !== 1) {
    http_response_code(403);

    die(
        'Nové uživatelské účty může vytvářet '
        . 'pouze superadministrátor.'
    );
}


/*
---------------------------------------------------
 Výchozí hodnoty
---------------------------------------------------
*/

$error = '';
$success = '';

$username = '';
$email = '';

$role = 'client';

$projectId = 0;
$projectRole = 'viewer';

$newUserIsSuperAdmin = 0;

$generatedInviteUrl = '';
$inviteExpiresAt = '';

$emailSubject = '';
$emailBody = '';


/*
---------------------------------------------------
 Projekty dostupné pro přiřazení
---------------------------------------------------
*/

$projects = [];

if ($isSuperAdmin === 1) {

    $projectStmt =
        $mysqli->prepare(
            'SELECT
                id,
                name
             FROM projects
             ORDER BY name, id'
        );

} else {

    $projectStmt =
        $mysqli->prepare(
            'SELECT DISTINCT
                p.id,
                p.name

             FROM projects p

             LEFT JOIN project_users pu
                ON pu.project_id = p.id
                AND pu.user_id = ?

             WHERE
                p.owner_id = ?
                OR (
                    pu.user_id = ?
                    AND pu.project_role IN (
                        "owner",
                        "admin"
                    )
                )

             ORDER BY
                p.name,
                p.id'
        );

    if ($projectStmt) {

        $projectStmt->bind_param(
            'iii',
            $currentUserId,
            $currentUserId,
            $currentUserId
        );
    }
}


if ($projectStmt) {

    $projectStmt->execute();

    $projectResult =
        $projectStmt->get_result();

    while (
        $project =
            $projectResult->fetch_assoc()
    ) {
        $projects[] = $project;
    }

    $projectStmt->close();
}


/*
---------------------------------------------------
 Uložení nového uživatele
---------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $username =
        trim(
            $_POST['username']
            ?? ''
        );

    $email =
        trim(
            $_POST['email']
            ?? ''
        );

    $role =
        $_POST['role']
        ?? 'client';

    $projectId =
        (int) (
            $_POST['project_id']
            ?? 0
        );

    $projectRole =
        $_POST['project_role']
        ?? 'viewer';

    $newUserIsSuperAdmin =
        isset($_POST['is_super_admin'])
            ? 1
            : 0;


    /*
     * Validace uživatelského jména.
     */
    if ($username === '') {

        $error =
            'Zadejte uživatelské jméno.';

    } elseif (
        strlen($username) < 3
    ) {

        $error =
            'Uživatelské jméno musí mít '
            . 'alespoň 3 znaky.';
    }


    /*
     * E-mail je pro pozvánku povinný.
     */
    if (
        $error === ''
        && $email === ''
    ) {

        $error =
            'Zadejte e-mail uživatele.';
    }


    if (
        $error === ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Zadaný e-mail není platný.';
    }


    /*
     * Globální role.
     */
    if (
        !in_array(
            $role,
            [
                'admin',
                'editor',
                'client',
                'viewer'
            ],
            true
        )
    ) {
        $role = 'client';
    }


    /*
     * Projektová role.
     */
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
        $projectRole =
            'viewer';
    }


    /*
     * Superadmina smí vytvářet pouze superadmin.
     */
    if ($isSuperAdmin !== 1) {
        $newUserIsSuperAdmin = 0;
    }


    /*
---------------------------------------------------
 Kontrola duplicity
---------------------------------------------------
*/

    if ($error === '') {

        $duplicateStmt =
            $mysqli->prepare(
                'SELECT
                    id,
                    username,
                    email
                 FROM users
                 WHERE username = ?
                    OR email = ?
                 LIMIT 1'
            );

        if (!$duplicateStmt) {

            $error =
                'Kontrolu uživatele se '
                . 'nepodařilo připravit.';

        } else {

            $duplicateStmt->bind_param(
                'ss',
                $username,
                $email
            );

            $duplicateStmt->execute();

            $duplicateUser =
                $duplicateStmt
                    ->get_result()
                    ->fetch_assoc();

            $duplicateStmt->close();

            if ($duplicateUser) {

                if (
                    strcasecmp(
                        (string) $duplicateUser['username'],
                        $username
                    ) === 0
                ) {

                    $error =
                        'Uživatel s tímto jménem '
                        . 'už existuje.';

                } else {

                    $error =
                        'Uživatel s tímto e-mailem '
                        . 'už existuje.';
                }
            }
        }
    }


    /*
---------------------------------------------------
 Ověření projektu
---------------------------------------------------
*/

    if (
        $error === ''
        && $projectId > 0
    ) {

        if ($isSuperAdmin === 1) {

            $projectCheckStmt =
                $mysqli->prepare(
                    'SELECT id
                     FROM projects
                     WHERE id = ?
                     LIMIT 1'
                );

            $projectCheckStmt->bind_param(
                'i',
                $projectId
            );

        } else {

            $projectCheckStmt =
                $mysqli->prepare(
                    'SELECT DISTINCT
                        p.id

                     FROM projects p

                     LEFT JOIN project_users pu
                        ON pu.project_id = p.id
                        AND pu.user_id = ?

                     WHERE
                        p.id = ?
                        AND (
                            p.owner_id = ?
                            OR (
                                pu.user_id = ?
                                AND pu.project_role IN (
                                    "owner",
                                    "admin"
                                )
                            )
                        )

                     LIMIT 1'
                );

            $projectCheckStmt->bind_param(
                'iiii',
                $currentUserId,
                $projectId,
                $currentUserId,
                $currentUserId
            );
        }

        $projectCheckStmt->execute();

        $projectExists =
            $projectCheckStmt
                ->get_result()
                ->fetch_assoc();

        $projectCheckStmt->close();

        if (!$projectExists) {

            $error =
                'K vybranému projektu '
                . 'nemáte oprávnění.';
        }
    }


    /*
---------------------------------------------------
 Vytvoření účtu + pozvánky
---------------------------------------------------
*/

    if ($error === '') {

        $mysqli->begin_transaction();

        try {

            /*
             * Vytvoříme náhodné heslo,
             * které uživatel nikdy nezná.
             *
             * Skutečné heslo si nastaví
             * přes pozvánku.
             */
            $temporaryPassword =
                bin2hex(
                    random_bytes(32)
                );

            $temporaryPasswordHash =
                password_hash(
                    $temporaryPassword,
                    PASSWORD_DEFAULT
                );

            if (
                $temporaryPasswordHash
                === false
            ) {

                throw new RuntimeException(
                    'Nelze vytvořit dočasné heslo.'
                );
            }


            /*
             * Účet zatím není aktivní.
             */
            $active = 0;


            $userStmt =
                $mysqli->prepare(
                    'INSERT INTO users (
                        username,
                        password,
                        email,
                        role,
                        is_super_admin,
                        active
                     )
                     VALUES (?, ?, ?, ?, ?, ?)'
                );

            if (!$userStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }


            $userStmt->bind_param(
                'ssssii',
                $username,
                $temporaryPasswordHash,
                $email,
                $role,
                $newUserIsSuperAdmin,
                $active
            );


            if (!$userStmt->execute()) {

                throw new RuntimeException(
                    $userStmt->error
                );
            }


            $newUserId =
                (int) $userStmt
                    ->insert_id;

            $userStmt->close();


            /*
             * Volitelné přiřazení do projektu.
             */
            if ($projectId > 0) {

                $projectUserStmt =
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

                if (!$projectUserStmt) {

                    throw new RuntimeException(
                        $mysqli->error
                    );
                }

                $projectUserStmt->bind_param(
                    'iis',
                    $projectId,
                    $newUserId,
                    $projectRole
                );


                if (
                    !$projectUserStmt
                        ->execute()
                ) {

                    throw new RuntimeException(
                        $projectUserStmt->error
                    );
                }

                $projectUserStmt->close();
            }


            /*
---------------------------------------------------
 Pozvánka
---------------------------------------------------
*/

            $token =
                bin2hex(
                    random_bytes(32)
                );

            $tokenHash =
                hash(
                    'sha256',
                    $token
                );


            /*
             * Platnost 7 dní.
             */
            $expiresAt =
                (new DateTimeImmutable())
                    ->modify('+7 days')
                    ->format(
                        'Y-m-d H:i:s'
                    );


            $inviteStmt =
                $mysqli->prepare(
                    'INSERT INTO user_invitations (
                        user_id,
                        token_hash,
                        expires_at,
                        created_by
                     )
                     VALUES (?, ?, ?, ?)'
                );

            if (!$inviteStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }


            $inviteStmt->bind_param(
                'issi',
                $newUserId,
                $tokenHash,
                $expiresAt,
                $currentUserId
            );


            if (
                !$inviteStmt->execute()
            ) {

                throw new RuntimeException(
                    $inviteStmt->error
                );
            }

            $inviteStmt->close();


            $mysqli->commit();


            /*
---------------------------------------------------
 Výstup pro admina
---------------------------------------------------
*/

            $generatedInviteUrl =
                svmUrl()
                . '/accept_invitation.php?token='
                . rawurlencode($token);


            $inviteExpiresAt =
                $expiresAt;


            $emailSubject =
                'Pozvánka do aplikace Stromy v mapě';


$emailBody =
    "Dobrý den,\n\n"
    . "byl Vám vytvořen přístup "
    . "do aplikace Stromy v mapě.\n\n"
    . "Pro nastavení hesla a aktivaci účtu "
    . "použijte následující odkaz:\n\n"
    . $generatedInviteUrl
    . "\n\n"
    . "Pozvánka je platná 7 dní.\n\n"
    . "Uživatelské jméno: "
    . $username
    . "\n\n"
    . "Stromy v mapě";


$htmlBody =
    '<p>Dobrý den,</p>'
    . '<p>byl Vám vytvořen přístup '
    . 'do aplikace <strong>Stromy v mapě</strong>.</p>'
    . '<p>Uživatelské jméno: <strong>'
    . htmlspecialchars(
        $username,
        ENT_QUOTES,
        'UTF-8'
    )
    . '</strong></p>'
    . '<p>'
    . '<a href="'
    . htmlspecialchars(
        $generatedInviteUrl,
        ENT_QUOTES,
        'UTF-8'
    )
    . '">'
    . 'Nastavit heslo a aktivovat účet'
    . '</a>'
    . '</p>'
    . '<p>Pozvánka je platná 7 dní.</p>'
    . '<p>Stromy v mapě</p>';


$mailSent =
    sendAppMail(
        $email,
        $emailSubject,
        $htmlBody,
        $emailBody
    );


if ($mailSent) {

    $success =
        'Uživatel byl vytvořen '
        . 'a pozvánka byla odeslána na e-mail.';

} else {

    $success =
        'Uživatel byl vytvořen, '
        . 'ale pozvánku se nepodařilo odeslat. '
        . 'Aktivační odkaz můžete použít ručně.';
}


            /*
             * Formulář už po úspěchu
             * není potřeba znovu plnit.
             */
            $projectId = 0;

        } catch (Throwable $exception) {

            $mysqli->rollback();

            error_log(
                'user_new.php: '
                . $exception->getMessage()
            );

            $error =
                'Uživatele se nepodařilo vytvořit.';
        }
    }
}


/*
---------------------------------------------------
 Vzhled
---------------------------------------------------
*/

$pageTitle =
    'Nový uživatel';

$pageSubtitle =
    'Správa uživatelských účtů';

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


<?php if (
    $generatedInviteUrl !== ''
): ?>

    <section class="admin-section">

        <h2>
            Pozvánka uživatele
        </h2>


        <div class="admin-field">

            <label for="invite_url">
                Aktivační odkaz
            </label>

            <input
                type="text"
                id="invite_url"
                value="<?= htmlspecialchars(
                    $generatedInviteUrl,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                readonly
            >

        </div>


        <p>
            Platnost pozvánky:
            <strong>
                <?= htmlspecialchars(
                    $inviteExpiresAt,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>
        </p>


        <div class="admin-actions">

            <button
                type="button"
                class="btn"
                onclick="copyInvitation()"
            >
                Kopírovat pozvánku
            </button>


            <a
                href="mailto:<?= rawurlencode(
                    $email
                ) ?>?subject=<?= rawurlencode(
                    $emailSubject
                ) ?>&body=<?= rawurlencode(
                    $emailBody
                ) ?>"
                class="btn btn-secondary"
            >
                Připravit e-mail
            </a>

        </div>

    </section>


<?php else: ?>


    <h2>
        Nový uživatel
    </h2>


    <form
        method="post"
        class="admin-form"
    >
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

        <div class="admin-field">

            <label for="username">
                Uživatelské jméno
            </label>

            <input
                type="text"
                id="username"
                name="username"
                maxlength="50"
                required
                autocomplete="off"
                value="<?= htmlspecialchars(
                    $username,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

        </div>


        <div class="admin-field">

            <label for="email">
                E-mail
            </label>

            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                required
                autocomplete="off"
                value="<?= htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

        </div>


        <div class="admin-field">

            <label for="role">
                Globální role
            </label>

            <select
                id="role"
                name="role"
            >

                <option
                    value="client"
                    <?= $role === 'client'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Zákazník
                </option>

                <option
                    value="viewer"
                    <?= $role === 'viewer'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Prohlížení
                </option>

                <option
                    value="editor"
                    <?= $role === 'editor'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Editor
                </option>

                <option
                    value="admin"
                    <?= $role === 'admin'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Administrátor
                </option>

            </select>

        </div>


        <div class="admin-field">

            <label for="project_id">
                Projekt
                <small>
                    (nepovinné)
                </small>
            </label>

            <select
                id="project_id"
                name="project_id"
            >

                <option value="0">
                    — zatím bez projektu —
                </option>

                <?php foreach (
                    $projects
                    as $project
                ): ?>

                    <option
                        value="<?= (int) $project['id'] ?>"
                        <?= (
                            (int) $project['id']
                            === $projectId
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
                        <?= htmlspecialchars(
                            $project['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
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

                <option
                    value="viewer"
                    <?= $projectRole === 'viewer'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Prohlížení
                </option>

                <option
                    value="editor"
                    <?= $projectRole === 'editor'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Editor
                </option>

                <option
                    value="admin"
                    <?= $projectRole === 'admin'
                        ? 'selected'
                        : ''
                    ?>
                >
                    Administrátor projektu
                </option>

            </select>

        </div>


        <?php if (
            $isSuperAdmin === 1
        ): ?>

            <div class="admin-field">

                <label>

                    <input
                        type="checkbox"
                        name="is_super_admin"
                        value="1"
                        <?= $newUserIsSuperAdmin === 1
                            ? 'checked'
                            : ''
                        ?>
                    >

                    Superadministrátor

                </label>

            </div>

        <?php endif; ?>


        <div class="admin-actions">

            <button
                type="submit"
                class="btn"
            >
                Vytvořit účet a pozvánku
            </button>

            <a
                href="/users.php"
                class="btn btn-secondary"
            >
                Zrušit
            </a>

        </div>

    </form>


<?php endif; ?>


<script>

function copyInvitation() {

    const input =
        document.getElementById(
            'invite_url'
        );

    if (!input) {
        return;
    }

    navigator.clipboard
        .writeText(input.value)
        .then(function () {

            alert(
                'Pozvánka byla zkopírována.'
            );

        })
        .catch(function () {

            input.select();

            document.execCommand(
                'copy'
            );

            alert(
                'Pozvánka byla zkopírována.'
            );
        });
}

</script>


<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>