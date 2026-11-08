<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';



require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';
require_once __DIR__ . '/app/mail/send_mail.php';

$currentUserId =
    (int) ($_SESSION['user_id'] ?? 0);

$currentRole =
    $_SESSION['role']
    ?? 'viewer';

$currentIsSuperAdmin =
    (int) (
        $_SESSION['is_super_admin']
        ?? 0
    );

$userId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$userId || $userId < 1) {
    http_response_code(400);
    die('Chybí platné ID uživatele.');
}


/*
---------------------------------------------------
 Oprávnění
---------------------------------------------------
*/

if ($currentIsSuperAdmin !== 1) {
    http_response_code(403);

    die(
        'Uživatelské účty může upravovat '
        . 'pouze superadministrátor.'
    );
}


/*
---------------------------------------------------
 Načtení uživatele
---------------------------------------------------
*/

$stmt =
    $mysqli->prepare(
        'SELECT
            id,
            username,
            email,
            role,
            is_super_admin,
            active,
            last_login,
            created_at
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

if (!$stmt) {
    http_response_code(500);
    die('Uživatele se nepodařilo načíst.');
}

$stmt->bind_param(
    'i',
    $userId
);

$stmt->execute();

$user =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();

if (!$user) {
    http_response_code(404);
    die('Uživatel nebyl nalezen.');
}


/*
 * Běžný admin nesmí upravovat superadmina.
 */
if (
    $currentIsSuperAdmin !== 1
    && (int) $user['is_super_admin'] === 1
) {
    http_response_code(403);

    die(
        'Superadministrátora může upravovat '
        . 'pouze jiný superadministrátor.'
    );
}


$error = '';
$success = '';

$username =
    (string) $user['username'];

$email =
    (string) ($user['email'] ?? '');

$role =
    (string) ($user['role'] ?? 'client');

$isSuperAdmin =
    (int) ($user['is_super_admin'] ?? 0);

$active =
    (int) ($user['active'] ?? 0);

$generatedInviteUrl = '';
$inviteExpiresAt = '';
$emailSubject = '';
$emailBody = '';


/*
---------------------------------------------------
 Zpracování formulářů
---------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {

    $action =
        $_POST['action']
        ?? 'save';


    /*
    -----------------------------------------------
     Uložení údajů uživatele
    -----------------------------------------------
    */

    if ($action === 'save') {

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

        $active =
            isset($_POST['active'])
                ? 1
                : 0;

        $isSuperAdmin =
            isset($_POST['is_super_admin'])
                ? 1
                : 0;


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


        if (
            $error === ''
            && $email !== ''
            && !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                'Zadaný e-mail není platný.';
        }


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
         * Běžný admin nesmí přidělit superadmina.
         */
        if (
            $currentIsSuperAdmin !== 1
        ) {
            $isSuperAdmin =
                (int) $user['is_super_admin'];
        }


        /*
         * Uživatel nesmí deaktivovat sám sebe.
         */
        if (
            $userId === $currentUserId
            && $active !== 1
        ) {

            $error =
                'Nemůžete deaktivovat vlastní účet.';
        }


        /*
         * Superadmin si nesmí sám odebrat
         * superadmin oprávnění.
         */
        if (
            $userId === $currentUserId
            && $currentIsSuperAdmin === 1
            && $isSuperAdmin !== 1
        ) {

            $error =
                'Nemůžete si odebrat vlastní '
                . 'oprávnění superadministrátora.';
        }


        /*
         * Duplicita jména / e-mailu.
         */
        if ($error === '') {

            $duplicateStmt =
                $mysqli->prepare(
                    'SELECT id
                     FROM users
                     WHERE
                        id <> ?
                        AND (
                            username = ?
                            OR (
                                email = ?
                                AND email <> ""
                            )
                        )
                     LIMIT 1'
                );

            if (!$duplicateStmt) {

                $error =
                    'Kontrolu duplicity se '
                    . 'nepodařilo připravit.';

            } else {

                $duplicateStmt->bind_param(
                    'iss',
                    $userId,
                    $username,
                    $email
                );

                $duplicateStmt->execute();

                $duplicate =
                    $duplicateStmt
                        ->get_result()
                        ->fetch_assoc();

                $duplicateStmt->close();

                if ($duplicate) {

                    $error =
                        'Uživatel se stejným jménem '
                        . 'nebo e-mailem už existuje.';
                }
            }
        }


        if ($error === '') {

            $updateStmt =
                $mysqli->prepare(
                    'UPDATE users
                     SET
                        username = ?,
                        email = ?,
                        role = ?,
                        is_super_admin = ?,
                        active = ?
                     WHERE id = ?
                     LIMIT 1'
                );

            if (!$updateStmt) {

                $error =
                    'Změny se nepodařilo připravit.';

            } else {

                $updateStmt->bind_param(
                    'sssiii',
                    $username,
                    $email,
                    $role,
                    $isSuperAdmin,
                    $active,
                    $userId
                );

                if ($updateStmt->execute()) {

                    $success =
                        'Uživatel byl úspěšně upraven.';

                    /*
                     * Pokud upravujeme sami sebe,
                     * obnovíme session.
                     */
                    if ($userId === $currentUserId) {

                        $_SESSION['username'] =
                            $username;

                        $_SESSION['role'] =
                            $role;

                        $_SESSION['is_super_admin'] =
                            $isSuperAdmin;

                        $_SESSION['active'] =
                            $active;
                    }

                } else {

                    $error =
                        'Uživatele se nepodařilo uložit.';
                }

                $updateStmt->close();
            }
        }
    }


    /*
    -----------------------------------------------
     Nová pozvánka
    -----------------------------------------------
    */

    if ($action === 'new_invitation') {

        if ($email === '') {

            $error =
                'Uživatel nemá zadaný e-mail.';

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                'E-mail uživatele není platný.';
        }


        if ($error === '') {

            $mysqli->begin_transaction();

            try {

                /*
                 * Staré nepoužité pozvánky
                 * zneplatníme.
                 */
                $invalidateStmt =
                    $mysqli->prepare(
                        'UPDATE user_invitations
                         SET used_at = NOW()
                         WHERE user_id = ?
                           AND used_at IS NULL'
                    );

                if (!$invalidateStmt) {

                    throw new RuntimeException(
                        $mysqli->error
                    );
                }

                $invalidateStmt->bind_param(
                    'i',
                    $userId
                );

                $invalidateStmt->execute();
                $invalidateStmt->close();


                /*
                 * Nový token.
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
                    $userId,
                    $tokenHash,
                    $expiresAt,
                    $currentUserId
                );


                if (!$inviteStmt->execute()) {

                    throw new RuntimeException(
                        $inviteStmt->error
                    );
                }

                $inviteStmt->close();


                /*
                 * Při nové pozvánce účet
                 * nastavíme jako neaktivní.
                 *
                 * Uživatel se znovu aktivuje
                 * až po nastavení hesla.
                 */
                $deactivateStmt =
                    $mysqli->prepare(
                        'UPDATE users
                         SET active = 0
                         WHERE id = ?
                         LIMIT 1'
                    );

                if (!$deactivateStmt) {

                    throw new RuntimeException(
                        $mysqli->error
                    );
                }

                $deactivateStmt->bind_param(
                    'i',
                    $userId
                );

                $deactivateStmt->execute();
                $deactivateStmt->close();


                $mysqli->commit();


                $active = 0;

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
    . "pro Váš účet v aplikaci "
    . "Stromy v mapě byla vytvořena "
    . "nová aktivační pozvánka.\n\n"
    . "Pro nastavení hesla a aktivaci účtu "
    . "použijte tento odkaz:\n\n"
    . $generatedInviteUrl
    . "\n\n"
    . "Pozvánka je platná 7 dní.\n\n"
    . "Uživatelské jméno: "
    . $username
    . "\n\n"
    . "Stromy v mapě";


$htmlBody =
    '<p>Dobrý den,</p>'
    . '<p>pro Váš účet v aplikaci '
    . '<strong>Stromy v mapě</strong> '
    . 'byla vytvořena nová aktivační pozvánka.</p>'
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
        'Nová pozvánka byla vytvořena '
        . 'a odeslána na e-mail.';

} else {

    $success =
        'Nová pozvánka byla vytvořena, '
        . 'ale e-mail se nepodařilo odeslat. '
        . 'Aktivační odkaz můžete použít ručně.';
}

            } catch (Throwable $exception) {

                $mysqli->rollback();

                error_log(
                    'user_edit.php invitation: '
                    . $exception->getMessage()
                );

                $error =
                    'Novou pozvánku se '
                    . 'nepodařilo vytvořit.';
            }
        }
    }
}


/*
---------------------------------------------------
 Projekty uživatele
---------------------------------------------------
*/

$userProjects = [];

$projectStmt =
    $mysqli->prepare(
        'SELECT
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
            p.owner_id = ?
            OR pu.user_id = ?

         ORDER BY
            p.name,
            p.id'
    );

if ($projectStmt) {

    $projectStmt->bind_param(
        'iiii',
        $userId,
        $userId,
        $userId,
        $userId
    );

    $projectStmt->execute();

    $projectResult =
        $projectStmt->get_result();

    while (
        $project =
            $projectResult->fetch_assoc()
    ) {
        $userProjects[] = $project;
    }

    $projectStmt->close();
}


$roleLabels = [
    'owner' => 'Vlastník',
    'admin' => 'Administrátor',
    'editor' => 'Editor',
    'viewer' => 'Prohlížení'
];


/*
---------------------------------------------------
 Vzhled
---------------------------------------------------
*/

$pageTitle =
    'Upravit uživatele';

$pageSubtitle =
    'Správa uživatelských účtů';

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


<?php if ($generatedInviteUrl !== ''): ?>

    <section class="admin-section">

        <h2>
            Nová pozvánka
        </h2>

        <div class="admin-field">

            <label for="invite_url">
                Aktivační odkaz
            </label>

            <input
                type="text"
                id="invite_url"
                readonly
                value="<?= htmlspecialchars(
                    $generatedInviteUrl,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

        </div>

        <p>
            Platnost:
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

<?php endif; ?>


<h2>
    Účet
</h2>


<form
    method="post"
    class="admin-form"
>

    <input
        type="hidden"
        name="action"
        value="save"
    >


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

        <label>
            <input
                type="checkbox"
                name="active"
                value="1"
                <?= $active === 1
                    ? 'checked'
                    : ''
                ?>
                <?= $userId === $currentUserId
                    ? 'disabled'
                    : ''
                ?>
            >

            Aktivní účet
        </label>

        <?php if (
            $userId === $currentUserId
        ): ?>

            <input
                type="hidden"
                name="active"
                value="1"
            >

        <?php endif; ?>

    </div>


    <?php if (
        $currentIsSuperAdmin === 1
    ): ?>

        <div class="admin-field">

            <label>

                <input
                    type="checkbox"
                    name="is_super_admin"
                    value="1"
                    <?= $isSuperAdmin === 1
                        ? 'checked'
                        : ''
                    ?>
                    <?= $userId === $currentUserId
                        ? 'disabled'
                        : ''
                    ?>
                >

                Superadministrátor

            </label>


            <?php if (
                $userId === $currentUserId
                && $isSuperAdmin === 1
            ): ?>

                <input
                    type="hidden"
                    name="is_super_admin"
                    value="1"
                >

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <div class="admin-actions">

        <button
            type="submit"
            class="btn"
        >
            Uložit změny
        </button>

    </div>

</form>


<section class="admin-section">

    <h2>
        Projekty uživatele
    </h2>

    <?php if (!$userProjects): ?>

        <p>
            Uživatel zatím nemá
            přiřazen žádný projekt.
        </p>

    <?php else: ?>

        <div class="project-list">

            <?php foreach (
                $userProjects
                as $project
            ): ?>

                <article class="project-card">

                    <div class="project-card__header">

                        <div>

                            <h3>
                                <?= htmlspecialchars(
                                    $project['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <div class="project-card__role">

                                <?= htmlspecialchars(
                                    $roleLabels[
                                        $project['project_role']
                                    ]
                                    ?? $project['project_role'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        </div>

                    </div>

                    <div class="project-card__actions">

                        <a
                            href="/project_users.php?id=<?= (int) $project['id'] ?>"
                            class="btn btn-secondary"
                        >
                            Správa přístupu
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<section class="admin-section">

    <h2>
        Přístup a heslo
    </h2>

    <p>
        Vytvořením nové pozvánky
        bude účet dočasně deaktivován.
        Uživatel si přes nový odkaz
        nastaví nové heslo a účet
        se znovu aktivuje.
    </p>

    <form
        method="post"
    >

        <input
            type="hidden"
            name="action"
            value="new_invitation"
        >

        <button
            type="submit"
            class="btn btn-secondary"
            onclick="
                return confirm(
                    'Vytvořit novou pozvánku? '
                    + 'Uživatel bude do jejího použití deaktivován.'
                );
            "
        >
            Vytvořit novou pozvánku
        </button>

    </form>

</section>


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