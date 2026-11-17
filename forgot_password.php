<?php
declare(strict_types=1);
require_once __DIR__.'/app/auth_form.php';
require_once __DIR__.'/app/bootstrap.php';



require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';
require_once __DIR__ . '/app/mail/send_mail.php';

header('Content-Type: text/html; charset=utf-8');

$mysqli->set_charset('utf8mb4');

$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email =
        trim(
            $_POST['email']
            ?? ''
        );

    require_once __DIR__.'/app/auth_limit.php';
    $limitLookup=$mysqli->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $limitLookup->execute([$email]);$limitUser=$limitLookup->get_result()->fetch_assoc();$limitLookup->close();
    svmEnforceAuthLimit('reset',$limitUser?'user:'.(int)$limitUser['id']:'email:'.mb_strtolower($email,'UTF-8'));

    /*
     * Vždy stejná neutrální odpověď.
     * Neprozradíme, zda účet existuje.
     */
    $message =
        'Pokud k této e-mailové adrese existuje účet, '
        . 'odeslali jsme instrukce pro nastavení nového hesla.';

    if (
        $email !== ''
        && filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $userStmt =
            $mysqli->prepare(
                'SELECT
                    id,
                    username,
                    email
                 FROM users
                 WHERE email = ?
                 LIMIT 1'
            );

        if ($userStmt) {

            $userStmt->bind_param(
                's',
                $email
            );

            $userStmt->execute();

            $user =
                $userStmt
                    ->get_result()
                    ->fetch_assoc();

            $userStmt->close();

            if ($user) {

                $userId =
                    (int) $user['id'];

                $username =
                    (string) $user['username'];

                /*
                 * Staré nepoužité reset tokeny
                 * zneplatníme.
                 */
                $invalidateStmt =
                    $mysqli->prepare(
                        'UPDATE password_reset_tokens
                         SET used_at = NOW()
                         WHERE user_id = ?
                           AND used_at IS NULL'
                    );

                if ($invalidateStmt) {

                    $invalidateStmt->bind_param(
                        'i',
                        $userId
                    );

                    $invalidateStmt->execute();
                    $invalidateStmt->close();
                }

                /*
                 * Nový bezpečný token.
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
                 * Platnost 60 minut.
                 */
                $expiresAt =
                    (new DateTimeImmutable())
                        ->modify('+60 minutes')
                        ->format(
                            'Y-m-d H:i:s'
                        );

                $resetStmt =
                    $mysqli->prepare(
                        'INSERT INTO password_reset_tokens (
                            user_id,
                            token_hash,
                            expires_at
                         )
                         VALUES (?, ?, ?)'
                    );

                if ($resetStmt) {

                    $resetStmt->bind_param(
                        'iss',
                        $userId,
                        $tokenHash,
                        $expiresAt
                    );

                    if ($resetStmt->execute()) {

                        $generatedResetUrl =
                            svmUrl()
                            . '/reset_password.php?token='
                            . rawurlencode($token);

                        $emailSubject =
                            'Obnovení hesla – Stromy v mapě';

                        $emailBody =
                            "Dobrý den,\n\n"
                            . "pro účet "
                            . $username
                            . " byl vytvořen požadavek "
                            . "na nastavení nového hesla.\n\n"
                            . "Odkaz:\n"
                            . $generatedResetUrl
                            . "\n\n"
                            . "Odkaz je platný 60 minut.\n\n"
                            . "Pokud jste o změnu hesla nežádali, "
                            . "můžete tento e-mail ignorovat.\n\n"
                            . "Stromy v mapě";

                        $htmlBody =
                            '<p>Dobrý den,</p>'
                            . '<p>pro účet <strong>'
                            . htmlspecialchars(
                                $username,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            . '</strong> byl vytvořen požadavek '
                            . 'na nastavení nového hesla.</p>'
                            . '<p>'
                            . '<a href="'
                            . htmlspecialchars(
                                $generatedResetUrl,
                                ENT_QUOTES,
                                'UTF-8'
                            )
                            . '">'
                            . 'Nastavit nové heslo'
                            . '</a>'
                            . '</p>'
                            . '<p>Odkaz je platný 60 minut.</p>'
                            . '<p>Pokud jste o změnu hesla nežádali, '
                            . 'můžete tento e-mail ignorovat.</p>'
                            . '<p>Stromy v mapě</p>';

                        sendAppMail(
                            $email,
                            $emailSubject,
                            $htmlBody,
                            $emailBody
                        );
                    }

                    $resetStmt->close();
                }
            }
        }
    }
}


$pageTitle =
    'Zapomenuté heslo';

$pageSubtitle =
    'Stromy v mapě';

$cardClass =
    'admin-card admin-card--small';

require_once __DIR__
    . '/app/includes/header.php';

?>

<h2>
    Zapomenuté heslo
</h2>

<p>
    Zadejte e-mail spojený s vaším účtem.
</p>

<?php if ($message !== ''): ?>

    <div
        class="admin-message admin-message--success"
    >
        <?= htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

    <div class="admin-actions">

        <a
            href="/login.php"
            class="btn btn-secondary"
        >
            Zpět na přihlášení
        </a>

    </div>

<?php else: ?>

    <form
        method="post"
        class="admin-form"
    >
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

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
                autocomplete="email"
                value="<?= htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

        </div>

        <div class="admin-actions">

            <button
                type="submit"
                class="btn"
            >
                Obnovit heslo
            </button>

            <a
                href="/login.php"
                class="btn btn-secondary"
            >
                Zpět na přihlášení
            </a>

        </div>

    </form>

<?php endif; ?>

<?php

require_once __DIR__
    . '/app/includes/footer.php';

?>