<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__.'/app/auth_form.php';
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$role = $_SESSION['role'] ?? 'viewer';
$isSuperAdmin = (int) ($_SESSION['is_super_admin'] ?? 0);

if (
    $isSuperAdmin !== 1
) {
    http_response_code(403);
    die('Nemáte oprávnění vytvářet zákazníky.');
}

$error = '';

$name = '';
$ico = '';
$address = '';
$email = '';
$phone = '';
$note = '';

$returnTo =
    $_GET['return_to']
    ?? $_POST['return_to']
    ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $ico = trim($_POST['ico'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($name === '') {
        $error = 'Zadejte název zákazníka.';
    }

    if (
        $error === ''
        && $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $error = 'Zadaný e-mail není platný.';
    }

    if ($error === '') {

        $stmt = $mysqli->prepare(
            'INSERT INTO clients (
                name,
                ico,
                address,
                email,
                phone,
                note
             )
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        if (!$stmt) {
            $error = 'Zákazníka se nepodařilo připravit.';
        } else {

            $stmt->bind_param(
                'ssssss',
                $name,
                $ico,
                $address,
                $email,
                $phone,
                $note
            );

if ($stmt->execute()) {

    /*
     * ID nově vytvořeného zákazníka
     * musíme získat ještě před close().
     */
    $newClientId =
        (int) $stmt->insert_id;

    $stmt->close();

    if ($returnTo === 'project_new') {

        header(
            'Location: /project_new.php?client_id='
            . $newClientId
        );

    } else {

        header(
            'Location: /clients.php'
        );
    }

    exit;

} else {

    $error =
        'Zákazníka se nepodařilo uložit.';

    $stmt->close();
}
        }
    }
}

$pageTitle = 'Nový zákazník';
$pageSubtitle = 'Správa zákazníků';
$cardClass = 'admin-card admin-card--small';

require_once __DIR__ . '/app/includes/header.php';
?>

<?php if ($error !== ''): ?>

    <div class="admin-message admin-message--error">
        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </div>

<?php endif; ?>

<h2>Nový zákazník</h2>

<form
    method="post"
    class="admin-form"
>
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

<input
    type="hidden"
    name="return_to"
    value="<?= htmlspecialchars(
        $returnTo,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

    <div class="admin-field">
        <label for="name">Název subjektu</label>

        <input
            type="text"
            id="name"
            name="name"
            maxlength="255"
            required
            value="<?= htmlspecialchars(
                $name,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </div>

    <div class="admin-field">
        <label for="ico">IČO</label>

        <input
            type="text"
            id="ico"
            name="ico"
            maxlength="20"
            value="<?= htmlspecialchars(
                $ico,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </div>

    <div class="admin-field">
        <label for="address">Adresa</label>

        <textarea
            id="address"
            name="address"
            rows="3"
        ><?= htmlspecialchars(
            $address,
            ENT_QUOTES,
            'UTF-8'
        ) ?></textarea>
    </div>

    <div class="admin-field">
        <label for="email">E-mail</label>

        <input
            type="email"
            id="email"
            name="email"
            maxlength="255"
            value="<?= htmlspecialchars(
                $email,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </div>

    <div class="admin-field">
        <label for="phone">Telefon</label>

        <input
            type="text"
            id="phone"
            name="phone"
            maxlength="50"
            value="<?= htmlspecialchars(
                $phone,
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >
    </div>

    <div class="admin-field">
        <label for="note">Poznámka</label>

        <textarea
            id="note"
            name="note"
            rows="4"
        ><?= htmlspecialchars(
            $note,
            ENT_QUOTES,
            'UTF-8'
        ) ?></textarea>
    </div>

    <div class="admin-actions">

        <button
            type="submit"
            class="btn"
        >
            Vytvořit zákazníka
        </button>

        <a
            href="/clients.php"
            class="btn btn-secondary"
        >
            Zrušit
        </a>

    </div>

</form>

<?php
require_once __DIR__ . '/app/includes/footer.php';
?>