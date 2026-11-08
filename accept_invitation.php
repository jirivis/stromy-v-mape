<?php

declare(strict_types=1);

require_once __DIR__
    . '/config_app.php';

require_once __DIR__
    . '/map/public/api/db.php';

header(
    'Content-Type: text/html; charset=utf-8'
);

$mysqli->set_charset('utf8mb4');


/*
---------------------------------------------------
 Pomocná funkce
---------------------------------------------------
*/

function inviteEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
---------------------------------------------------
 Token
---------------------------------------------------
*/

$token =
    trim(
        (string) (
            $_GET['token']
            ?? $_POST['token']
            ?? ''
        )
    );


/*
 * Token generujeme jako 64 hex znaků.
 */
if (
    $token === ''
    || !preg_match(
        '/^[a-f0-9]{64}$/',
        $token
    )
) {
    http_response_code(404);

    die(
        'Pozvánka nebyla nalezena.'
    );
}


$tokenHash =
    hash(
        'sha256',
        $token
    );


/*
---------------------------------------------------
 Načtení platné pozvánky
---------------------------------------------------
*/

$inviteStmt =
    $mysqli->prepare(
        'SELECT
            ui.id AS invitation_id,
            ui.user_id,
            ui.expires_at,
            ui.used_at,

            u.username,
            u.email,
            u.active

         FROM user_invitations ui

         INNER JOIN users u
            ON u.id = ui.user_id

         WHERE ui.token_hash = ?

         LIMIT 1'
    );

if (!$inviteStmt) {

    http_response_code(500);

    die(
        'Pozvánku se nepodařilo ověřit.'
    );
}


$inviteStmt->bind_param(
    's',
    $tokenHash
);

$inviteStmt->execute();

$invite =
    $inviteStmt
        ->get_result()
        ->fetch_assoc();

$inviteStmt->close();


if (!$invite) {

    http_response_code(404);

    die(
        'Pozvánka nebyla nalezena.'
    );
}


$invitationId =
    (int) $invite['invitation_id'];

$userId =
    (int) $invite['user_id'];

$username =
    (string) $invite['username'];

$email =
    (string) (
        $invite['email']
        ?? ''
    );


/*
---------------------------------------------------
 Stav pozvánky
---------------------------------------------------
*/

$alreadyUsed =
    !empty(
        $invite['used_at']
    );

$expiresTimestamp =
    strtotime(
        (string) $invite['expires_at']
    );

$isExpired =
    $expiresTimestamp === false
    || $expiresTimestamp < time();


$error = '';

$success = false;


/*
---------------------------------------------------
 Aktivace účtu
---------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
    && !$alreadyUsed
    && !$isExpired
) {

    $password =
        (string) (
            $_POST['password']
            ?? ''
        );

    $passwordAgain =
        (string) (
            $_POST['password_again']
            ?? ''
        );


    /*
     * Základní pravidla hesla.
     *
     * Nemusíme nutit speciální znak,
     * důležitější je dostatečná délka.
     */
    if (
        mb_strlen(
            $password,
            'UTF-8'
        ) < 10
    ) {

        $error =
            'Heslo musí mít alespoň 10 znaků.';

    } elseif (
        $password !==
        $passwordAgain
    ) {

        $error =
            'Zadaná hesla se neshodují.';
    }


    if ($error === '') {

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        if ($passwordHash === false) {

            $error =
                'Heslo se nepodařilo bezpečně uložit.';
        }
    }


    /*
---------------------------------------------------
 Transakce
---------------------------------------------------
*/

    if ($error === '') {

        $mysqli->begin_transaction();

        try {

            /*
             * Pozvánku ještě jednou zamkneme
             * a ověříme přímo v transakci.
             *
             * Tím zabráníme tomu, aby byl stejný
             * odkaz použit současně dvakrát.
             */
            $lockStmt =
                $mysqli->prepare(
                    'SELECT
                        user_id,
                        expires_at,
                        used_at

                     FROM user_invitations

                     WHERE id = ?

                     FOR UPDATE'
                );

            if (!$lockStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $lockStmt->bind_param(
                'i',
                $invitationId
            );

            $lockStmt->execute();

            $lockedInvite =
                $lockStmt
                    ->get_result()
                    ->fetch_assoc();

            $lockStmt->close();


            if (!$lockedInvite) {

                throw new RuntimeException(
                    'Pozvánka již neexistuje.'
                );
            }


            if (
                !empty(
                    $lockedInvite['used_at']
                )
            ) {

                throw new RuntimeException(
                    'Pozvánka již byla použita.'
                );
            }


            $lockedExpires =
                strtotime(
                    (string) $lockedInvite[
                        'expires_at'
                    ]
                );

            if (
                $lockedExpires === false
                || $lockedExpires < time()
            ) {

                throw new RuntimeException(
                    'Platnost pozvánky vypršela.'
                );
            }


            /*
             * Uložení skutečného hesla
             * a aktivace účtu.
             */
            $userStmt =
                $mysqli->prepare(
                    'UPDATE users
                     SET
                        password = ?,
                        active = 1
                     WHERE id = ?
                     LIMIT 1'
                );

            if (!$userStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $userStmt->bind_param(
                'si',
                $passwordHash,
                $userId
            );

            if (!$userStmt->execute()) {

                throw new RuntimeException(
                    $userStmt->error
                );
            }

            $userStmt->close();


            /*
             * Aktuální pozvánku označíme
             * jako použitou.
             */
            $usedStmt =
                $mysqli->prepare(
                    'UPDATE user_invitations
                     SET used_at = NOW()
                     WHERE id = ?
                       AND used_at IS NULL'
                );

            if (!$usedStmt) {

                throw new RuntimeException(
                    $mysqli->error
                );
            }

            $usedStmt->bind_param(
                'i',
                $invitationId
            );

            if (!$usedStmt->execute()) {

                throw new RuntimeException(
                    $usedStmt->error
                );
            }

            $usedStmt->close();


            /*
             * Pokud by uživatel měl ještě jinou
             * nepoužitou pozvánku, zneplatníme ji.
             *
             * Použijeme used_at jako označení
             * zneplatněného odkazu.
             */
            $invalidateStmt =
                $mysqli->prepare(
                    'UPDATE user_invitations
                     SET used_at = NOW()
                     WHERE user_id = ?
                       AND id <> ?
                       AND used_at IS NULL'
                );

            if ($invalidateStmt) {

                $invalidateStmt->bind_param(
                    'ii',
                    $userId,
                    $invitationId
                );

                $invalidateStmt->execute();

                $invalidateStmt->close();
            }


            $mysqli->commit();

            $success = true;

            $alreadyUsed = true;

        } catch (Throwable $exception) {

            $mysqli->rollback();

            error_log(
                'accept_invitation.php: '
                . $exception->getMessage()
            );

            $error =
                'Aktivaci účtu se nepodařilo dokončit. '
                . 'Pozvánka mohla být mezitím použita '
                . 'nebo jí vypršela platnost.';
        }
    }
}


/*
---------------------------------------------------
 Vzhled
---------------------------------------------------
*/

$pageTitle =
    'Aktivace účtu';

$pageSubtitle =
    'Stromy v mapě';

$cardClass =
    'admin-card admin-card--small';


/*
 * Tato stránka je veřejná,
 * takže NESMÍ načítat auth.php.
 *
 * header.php by proto také neměl
 * předpokládat přihlášeného uživatele.
 */
require_once __DIR__
    . '/app/includes/header.php';

?>


<?php if ($success): ?>

    <div
        class="admin-message admin-message--success"
    >
        Účet byl úspěšně aktivován.
    </div>

    <h2>
        Vítejte v aplikaci Stromy v mapě
    </h2>

    <p>
        Heslo bylo nastaveno
        a účet
        <strong>
            <?= inviteEscape(
                $username
            ) ?>
        </strong>
        je nyní aktivní.
    </p>

    <div class="admin-actions">

        <a
            href="/login.php"
            class="btn"
        >
            Přihlásit se
        </a>

    </div>


<?php elseif ($alreadyUsed): ?>

    <div
        class="admin-message admin-message--error"
    >
        Tato pozvánka již byla použita
        a není možné ji použít znovu.
    </div>

    <div class="admin-actions">

        <a
            href="/login.php"
            class="btn"
        >
            Přejít na přihlášení
        </a>

    </div>


<?php elseif ($isExpired): ?>

    <div
        class="admin-message admin-message--error"
    >
        Platnost této pozvánky již vypršela.
    </div>

    <p>
        Požádejte správce aplikace
        o vytvoření nové pozvánky.
    </p>

    <div class="admin-actions">

        <a
            href="/login.php"
            class="btn btn-secondary"
        >
            Přejít na přihlášení
        </a>

    </div>


<?php else: ?>

    <?php if ($error !== ''): ?>

        <div
            class="admin-message admin-message--error"
        >
            <?= inviteEscape(
                $error
            ) ?>
        </div>

    <?php endif; ?>


    <h2>
        Aktivace účtu
    </h2>


    <div class="invitation-account">

        <div>
            <span>
                Uživatelské jméno
            </span>

            <strong>
                <?= inviteEscape(
                    $username
                ) ?>
            </strong>
        </div>


        <?php if ($email !== ''): ?>

            <div>
                <span>
                    E-mail
                </span>

                <strong>
                    <?= inviteEscape(
                        $email
                    ) ?>
                </strong>
            </div>

        <?php endif; ?>

    </div>


    <p>
        Pro dokončení registrace
        si nastavte vlastní heslo.
    </p>


    <form
        method="post"
        class="admin-form"
    >

        <input
            type="hidden"
            name="token"
            value="<?= inviteEscape(
                $token
            ) ?>"
        >


        <div class="admin-field">

            <label for="password">
                Nové heslo
            </label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="10"
                required
                autocomplete="new-password"
            >

            <small>
                Minimálně 10 znaků.
            </small>

        </div>


        <div class="admin-field">

            <label for="password_again">
                Heslo znovu
            </label>

            <input
                type="password"
                id="password_again"
                name="password_again"
                minlength="10"
                required
                autocomplete="new-password"
            >

        </div>


        <div class="admin-actions">

            <button
                type="submit"
                class="btn"
            >
                Nastavit heslo a aktivovat účet
            </button>

        </div>

    </form>

<?php endif; ?>


<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>