<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/bootstrap.php';



require_once __DIR__ . '/../../auth_api.php';

requireProjectRole([
    'owner',
    'admin',
    'editor'
]);

require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$_SESSION['tree_form_csrf']??=bin2hex(random_bytes(32));
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&!hash_equals($_SESSION['tree_form_csrf'],(string)($_POST['tree_form_token']??''))){http_response_code(403);exit('Platnost formuláře vypršela. Obnovte stránku.');}

$mysqli->set_charset('utf8mb4');

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
        'Ke sdílení karet v tomto projektu '
        . 'nemáte oprávnění.'
    );
}

$treeId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$treeId || $treeId < 1) {
    http_response_code(400);
    die('Chybí platné ID stromu.');
}


/*
 * Strom musí patřit do aktivního projektu.
 */
$treeStatement = $mysqli->prepare(
    'SELECT
        id,
        usr_id,
        species,
        czech
     FROM trees
     WHERE id = ?
       AND project_id = ?
     LIMIT 1'
);

if (!$treeStatement) {
    http_response_code(500);
    die('Kontrolu stromu se nepodařilo připravit.');
}

$treeStatement->bind_param(
    'ii',
    $treeId,
    $activeProjectId
);

$treeStatement->execute();

$treeResult =
    $treeStatement->get_result();

$tree =
    $treeResult->fetch_assoc();

$treeStatement->close();

if (!$tree) {
    http_response_code(404);

    die(
        'Strom nebyl nalezen '
        . 'v aktivním projektu.'
    );
}

$treeNumber =
    $tree['usr_id']
    ?? $tree['id'];

$treeName =
    trim(
        (string) (
            $tree['czech']
            ?: $tree['species']
        )
    );


$message = '';
$messageType = '';
$generatedUrl = '';

$action =
    $_POST['action']
    ?? '';


/*
---------------------------------------------------
 Vytvoření nebo obnovení odkazu
---------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array(
        $action,
        ['create', 'regenerate'],
        true
    )
) {
    /*
     * Staré odkazy tohoto stromu deaktivujeme.
     */
    $disableStatement = $mysqli->prepare(
        'UPDATE tree_shares
         SET active = 0
         WHERE tree_id = ?'
    );

    if ($disableStatement) {
        $disableStatement->bind_param(
            'i',
            $treeId
        );

        $disableStatement->execute();
        $disableStatement->close();
    }


    /*
     * Skutečný token se ukáže jen uživateli.
     * Do databáze uložíme pouze jeho hash.
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

    $userId =
        (int) $_SESSION['user_id'];

    $label =
        trim(
            $_POST['label']
            ?? ''
        );

    $label =
        $label !== ''
            ? $label
            : null;

    $insertStatement = $mysqli->prepare(
        'INSERT INTO tree_shares (
            tree_id,
            token_hash,
            label,
            active,
            created_by
         )
         VALUES (?, ?, ?, 1, ?)'
    );

    if (!$insertStatement) {
        http_response_code(500);

        die(
            'Sdílený odkaz se nepodařilo připravit.'
        );
    }

    $insertStatement->bind_param(
        'issi',
        $treeId,
        $tokenHash,
        $label,
        $userId
    );

    if (!$insertStatement->execute()) {
        http_response_code(500);

        die(
            'Sdílený odkaz se nepodařilo vytvořit.'
        );
    }

    $insertStatement->close();

    $generatedUrl =
        svmUrl()
        . '/map/public/public_tree.php?token='
        . rawurlencode($token);
		
$emailSubject =
    'Karta stromu č. '
    . $treeNumber
    . (
        $treeName !== ''
            ? ' – ' . $treeName
            : ''
    );

$emailBody =
    "Dobrý den,\n\n"
    . "na následujícím odkazu naleznete "
    . "kartu stromu č. "
    . $treeNumber
    . ":\n\n"
    . $generatedUrl
    . "\n\n"
    . "Odkaz můžete kdykoliv znovu otevřít "
    . "a kartu také vytisknout nebo uložit do PDF.\n\n"
    . "S pozdravem";

    $message =
        $action === 'regenerate'
            ? 'Byl vytvořen nový odkaz. '
                . 'Původní odkaz už neplatí.'
            : 'Sdílený odkaz byl vytvořen.';

    $messageType = 'success';
}


/*
---------------------------------------------------
 Zrušení odkazu
---------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $action === 'revoke'
) {
    $revokeStatement = $mysqli->prepare(
        'UPDATE tree_shares
         SET active = 0
         WHERE tree_id = ?'
    );

    if (!$revokeStatement) {
        http_response_code(500);
        die('Odkaz se nepodařilo zrušit.');
    }

    $revokeStatement->bind_param(
        'i',
        $treeId
    );

    $revokeStatement->execute();
    $revokeStatement->close();

    $message =
        'Veřejný odkaz byl zrušen.';

    $messageType = 'success';
}


/*
 * Aktuální stav sdílení.
 *
 * Skutečný token nelze z hashe zpětně získat.
 */
$shareStatement = $mysqli->prepare(
    'SELECT
        id,
        label,
        created_at,
        last_access_at
     FROM tree_shares
     WHERE tree_id = ?
       AND active = 1
       AND (
            expires_at IS NULL
            OR expires_at > NOW()
       )
     ORDER BY id DESC
     LIMIT 1'
);

$activeShare = null;

if ($shareStatement) {
    $shareStatement->bind_param(
        'i',
        $treeId
    );

    $shareStatement->execute();

    $shareResult =
        $shareStatement->get_result();

    $activeShare =
        $shareResult->fetch_assoc();

    $shareStatement->close();
}

?>
<!DOCTYPE html>

<html lang="cs">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Sdílení karty stromu
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;

            background: #eef2ef;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #183022;
        }

        .share-card {
            width: min(680px, 100%);
            margin: 0 auto;
            padding: 28px;

            background: #ffffff;

            border-top: 5px solid #28753b;
            border-radius: 8px;

            box-shadow:
                0 6px 25px
                rgba(0, 0, 0, 0.12);
        }

        h1 {
            margin-top: 0;
        }

        .message {
            margin: 18px 0;
            padding: 12px 14px;

            border-left: 5px solid #28753b;

            background: #eaf6ed;
        }

        .share-url {
            width: 100%;
            padding: 10px;

            border: 1px solid #aab7ad;
            border-radius: 5px;

            font-family: monospace;
            font-size: 13px;
        }

        .field {
            margin-top: 18px;
        }

        .field label {
            display: block;
            margin-bottom: 5px;

            font-weight: 700;
        }

        .field input {
            width: 100%;
            padding: 9px;

            border: 1px solid #aab7ad;
            border-radius: 5px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;

            margin-top: 20px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 10px 15px;

            border: 1px solid #28753b;
            border-radius: 5px;

            background: #28753b;
            color: #ffffff;

            font-weight: 700;
            text-decoration: none;

            cursor: pointer;
        }

        .button--secondary {
            background: #ffffff;
            color: #245f32;
        }

        .button--danger {
            border-color: #9b2c2c;
            background: #a93737;
        }

        .status {
            margin-top: 18px;
            padding: 12px;

            background: #f3f6f4;
            border-radius: 5px;
        }

        small {
            color: #68746c;
        }

    </style>

</head>

<body>

<main class="share-card">

    <h1>
        Sdílení karty stromu
    </h1>

    <p>
        Strom č.
        <strong>
            <?= htmlspecialchars(
                (string) $treeNumber,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>

        <?php if ($treeName !== ''): ?>

            –
            <?= htmlspecialchars(
                $treeName,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        <?php endif; ?>
    </p>


    <?php if ($message !== ''): ?>

        <div class="message">
            <?= htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>


    <?php if ($generatedUrl !== ''): ?>

        <div class="field">

            <label for="generatedUrl">
                Nový veřejný odkaz
            </label>

            <input
                type="text"
                id="generatedUrl"
                class="share-url"
                value="<?= htmlspecialchars(
                    $generatedUrl,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                readonly
            >

            <div class="actions">

                <button
                    type="button"
                    class="button"
                    onclick="copyShareUrl()"
                >
                    Kopírovat odkaz
                </button>

                <a
                    href="<?= htmlspecialchars(
                        $generatedUrl,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    class="button button--secondary"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Otevřít veřejnou kartu
                </a>
				
				<button type="button" class="button" id="shareDevice" hidden
        data-title="<?= htmlspecialchars($emailSubject, ENT_QUOTES, 'UTF-8') ?>">
        Sdílet…
    </button>
    <a href="mailto:?subject=<?= rawurlencode($emailSubject) ?>&amp;body=<?= rawurlencode($emailBody) ?>"
       class="button">E-mailem</a>
    <a href="https://wa.me/?text=<?= rawurlencode($emailSubject . "\n" . $generatedUrl) ?>"
       class="button" target="_blank" rel="noopener noreferrer">WhatsApp</a>


            </div>

            <p id="shareStatus" role="status" aria-live="polite"></p>
            <p><small>Vyberte aplikaci a příjemce. Zprávu odešlete sami ve své aplikaci.</small></p>
            <p>
                <small>
                    Tento odkaz si nyní uložte.
                    Z databáze ho později nelze
                    znovu zobrazit.
                </small>
            </p>

        </div>

    <?php endif; ?>


    <?php if ($activeShare): ?>

        <div class="status">

            <strong>
                Veřejné sdílení je aktivní.
            </strong>

            <p>
                Vytvořeno:
                <?= htmlspecialchars(
                    (string) $activeShare['created_at'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

            <?php if (!empty($activeShare['last_access_at'])): ?>

                <p>
                    Naposledy otevřeno:
                    <?= htmlspecialchars(
                        (string) $activeShare['last_access_at'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            <?php endif; ?>

        </div>

        <div class="actions">

            <form method="post">
<input type="hidden" name="tree_form_token" value="<?= htmlspecialchars($_SESSION['tree_form_csrf'],ENT_QUOTES,'UTF-8') ?>">

                <input
                    type="hidden"
                    name="action"
                    value="regenerate"
                >

                <button
                    type="submit"
                    class="button"
                >
                    Vytvořit nový odkaz
                </button>

            </form>

            <form
                method="post"
                onsubmit="
                    return confirm(
                        'Opravdu zrušit veřejný odkaz?'
                    );
                "
            >
<input type="hidden" name="tree_form_token" value="<?= htmlspecialchars($_SESSION['tree_form_csrf'],ENT_QUOTES,'UTF-8') ?>">

                <input
                    type="hidden"
                    name="action"
                    value="revoke"
                >

                <button
                    type="submit"
                    class="button button--danger"
                >
                    Zrušit odkaz
                </button>

            </form>

        </div>

    <?php else: ?>

        <form method="post">
<input type="hidden" name="tree_form_token" value="<?= htmlspecialchars($_SESSION['tree_form_csrf'],ENT_QUOTES,'UTF-8') ?>">

            <input
                type="hidden"
                name="action"
                value="create"
            >

<div class="field">

    <label for="label">
        Poznámka k odkazu
        <small>(nepovinné)</small>
    </label>

    <input
        type="text"
        id="label"
        name="label"
        maxlength="255"
        placeholder="Např. odkaz pro vlastníka stromu"
        value="<?= htmlspecialchars(
            $_POST['label'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

</div>




            <div class="actions">

                <button
                    type="submit"
                    class="button"
                >
                    Vytvořit veřejný odkaz
                </button>

            </div>

        </form>

    <?php endif; ?>


    <div class="actions">

        <a
            href="karta.php?id=<?= (int) $treeId ?>"
            class="button button--secondary"
        >
            Zpět na kartu
        </a>

    </div>

</main>

<script>
const shareInput = document.getElementById('generatedUrl');
const shareStatus = document.getElementById('shareStatus');
const shareButton = document.getElementById('shareDevice');
function shareMessage(text) { if (shareStatus) shareStatus.textContent = text; }
async function copyShareUrl() {
    if (!shareInput) return;
    try {
        if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
        await navigator.clipboard.writeText(shareInput.value);
        shareMessage('Odkaz byl zkopírován.');
    } catch {
        shareInput.focus(); shareInput.select();
        shareInput.setSelectionRange(0, shareInput.value.length);
        let copied = false;
        try { copied = document.execCommand('copy'); } catch {}
        shareMessage(copied ? 'Odkaz byl zkopírován.' : 'Odkaz je označený. Zkopírujte jej pomocí nabídky zařízení nebo Ctrl+C.');
    }
}
if (shareButton && shareInput && typeof navigator.share === 'function') {
    shareButton.hidden = false;
    shareButton.addEventListener('click', async () => {
        shareButton.disabled = true;
        try {
            await navigator.share({title: shareButton.dataset.title, text: shareButton.dataset.title, url: shareInput.value});
            shareMessage('Odkaz byl předán vybrané aplikaci.');
        } catch (error) {
            if (error.name !== 'AbortError') shareMessage('Sdílení se nepodařilo otevřít. Použijte E-mailem, WhatsApp nebo Kopírovat odkaz.');
        } finally { shareButton.disabled = false; }
    });
}
</script>

</body>
</html>