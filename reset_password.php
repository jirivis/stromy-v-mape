<?php

declare(strict_types=1);
require_once __DIR__.'/app/auth_form.php';

require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

header('Content-Type: text/html; charset=utf-8');

$mysqli->set_charset('utf8mb4');


function resetEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


$token =
    trim(
        (string) (
            $_GET['token']
            ?? $_POST['token']
            ?? ''
        )
    );

if (
    $token === ''
    || !preg_match(
        '/^[a-f0-9]{64}$/',
        $token
    )
) {
    http_response_code(404);
    die('Odkaz pro obnovení hesla nebyl nalezen.');
}


$tokenHash =
    hash(
        'sha256',
        $token
    );


$stmt =
    $mysqli->prepare(
        'SELECT
            prt.id AS reset_id,
            prt.user_id,
            prt.expires_at,
            prt.used_at,

            u.username,
            u.email

         FROM password_reset_tokens prt

         INNER JOIN users u
            ON u.id = prt.user_id

         WHERE prt.token_hash = ?

         LIMIT 1'
    );

$stmt->bind_param(
    's',
    $tokenHash
);

$stmt->execute();

$reset =
    $stmt
        ->get_result()
        ->fetch_assoc();

$stmt->close();


if (!$reset) {
    http_response_code(404);
    die('Odkaz pro obnovení hesla nebyl nalezen.');
}


$resetId =
    (int) $reset['reset_id'];

$userId =
    (int) $reset['user_id'];

$username =
    (string) $reset['username'];


$alreadyUsed =
    !empty(
        $reset['used_at']
    );

$expiresTimestamp =
    strtotime(
        (string) $reset['expires_at']
    );

$isExpired =
    $expiresTimestamp === false
    || $expiresTimestamp < time();


$error = '';
$success = false;


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
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

    if (strlen($password)>72 || str_contains($password,"\0")) {
        $error='Heslo může mít nejvýše 72 bajtů a nesmí obsahovat nulový znak.';
    } elseif (
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


    if ($error === '') {

        $mysqli->begin_transaction();

        try {

            /*
             * Zamkneme reset token.
             */
            $lockStmt =
                $mysqli->prepare(
                    'SELECT
                        user_id,
                        expires_at,
                        used_at
                     FROM password_reset_tokens
                     WHERE id = ?
                     FOR UPDATE'
                );

            $lockStmt->bind_param(
                'i',
                $resetId
            );

            $lockStmt->execute();

            $locked =
                $lockStmt
                    ->get_result()
                    ->fetch_assoc();

            $lockStmt->close();


            if (
                !$locked
                || !empty($locked['used_at'])
            ) {
                throw new RuntimeException(
                    'Reset již není platný.'
                );
            }


            $lockedExpires =
                strtotime(
                    (string) $locked['expires_at']
                );

            if (
                $lockedExpires === false
                || $lockedExpires < time()
            ) {
                throw new RuntimeException(
                    'Reset vypršel.'
                );
            }


            /*
             * Uložení nového hesla.
             *
             * Reset hesla nemění role ani projekty.
             */
            $userStmt =
                $mysqli->prepare(
                    'UPDATE users
                     SET password = ?
                     WHERE id = ?
                     LIMIT 1'
                );

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
             * Token označíme za použitý.
             */
            $usedStmt =
                $mysqli->prepare(
                    'UPDATE password_reset_tokens
                     SET used_at = NOW()
                     WHERE id = ?
                       AND used_at IS NULL'
                );

            $usedStmt->bind_param(
                'i',
                $resetId
            );

            if (!$usedStmt->execute()) {
                throw new RuntimeException(
                    $usedStmt->error
                );
            }

            $usedStmt->close();


            /*
             * Ostatní tokeny uživatele
             * rovnou zneplatníme.
             */
            $invalidateStmt =
                $mysqli->prepare(
                    'UPDATE password_reset_tokens
                     SET used_at = NOW()
                     WHERE user_id = ?
                       AND id <> ?
                       AND used_at IS NULL'
                );

            if ($invalidateStmt) {

                $invalidateStmt->bind_param(
                    'ii',
                    $userId,
                    $resetId
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
                'reset_password.php: '
                . $exception->getMessage()
            );

            $error =
                'Heslo se nepodařilo změnit. '
                . 'Odkaz mohl mezitím vypršet.';
        }
    }
}


$pageTitle =
    'Nastavení nového hesla';

$pageSubtitle =
    'Stromy v mapě';

$cardClass =
    'admin-card admin-card--small';

require_once __DIR__
    . '/app/includes/header.php';

?>


<?php if ($success): ?>

    <div class="admin-message admin-message--success">
        Heslo bylo úspěšně změněno.
    </div>

    <div class="admin-actions">

        <a
            href="/login.php"
            class="btn"
        >
            Přihlásit se
        </a>

    </div>


<?php elseif ($alreadyUsed): ?>

    <div class="admin-message admin-message--error">
        Tento odkaz už byl použit.
    </div>

    <div class="admin-actions">

        <a
            href="/forgot_password.php"
            class="btn btn-secondary"
        >
            Vytvořit nový odkaz
        </a>

    </div>


<?php elseif ($isExpired): ?>

    <div class="admin-message admin-message--error">
        Platnost odkazu vypršela.
    </div>

    <div class="admin-actions">

        <a
            href="/forgot_password.php"
            class="btn"
        >
            Vytvořit nový odkaz
        </a>

    </div>


<?php else: ?>

    <?php if ($error !== ''): ?>

        <div class="admin-message admin-message--error">
            <?= resetEscape($error) ?>
        </div>

    <?php endif; ?>


    <h2>
        Nové heslo
    </h2>

    <p>
        Účet:
        <strong>
            <?= resetEscape($username) ?>
        </strong>
    </p>


    <form
        method="post"
        class="admin-form"
    >
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

        <input
            type="hidden"
            name="token"
            value="<?= resetEscape($token) ?>"
        >


        <div class="admin-field">

            <label for="password">
                Nové heslo
            </label>

            <div class="password-field">

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="10"
                    required
                    autocomplete="new-password"
                >

                <button
                    type="button"
                    class="password-toggle"
                    data-password-toggle="password"
                    aria-label="Zobrazit heslo"
                >
                    👁
                </button>

            </div>

        </div>


        <div class="admin-field">

            <label for="password_again">
                Heslo znovu
            </label>

            <div class="password-field">

                <input
                    type="password"
                    id="password_again"
                    name="password_again"
                    minlength="10"
                    required
                    autocomplete="new-password"
                >

                <button
                    type="button"
                    class="password-toggle"
                    data-password-toggle="password_again"
                    aria-label="Zobrazit heslo"
                >
                    👁
                </button>

            </div>

        </div>


        <div class="admin-actions">

            <button
                type="submit"
                class="btn"
            >
                Nastavit nové heslo
            </button>

        </div>

    </form>

<?php endif; ?>


<script>

document.querySelectorAll(
    '[data-password-toggle]'
).forEach(function (button) {

    button.addEventListener(
        'click',
        function () {

            const input =
                document.getElementById(
                    button.dataset.passwordToggle
                );

            if (!input) {
                return;
            }

            const show =
                input.type === 'password';

            input.type =
                show
                    ? 'text'
                    : 'password';

            button.setAttribute(
                'aria-label',
                show
                    ? 'Skrýt heslo'
                    : 'Zobrazit heslo'
            );
        }
    );
});

</script>

<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>