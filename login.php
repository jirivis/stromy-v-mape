<?php

require_once __DIR__.'/app/auth_form.php';

require_once __DIR__ . '/config_app.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';

$errorMessages = [
    'session' => 'Platnost přihlášení skončila. Přihlaste se znovu.',
    'empty' => 'Vyplňte uživatelské jméno i heslo.',
    'invalid' => 'Nesprávné uživatelské jméno nebo heslo.',
    'inactive' => 'Tento uživatelský účet není aktivní.',
    'server' => 'Přihlášení se nyní nepodařilo zpracovat.'
];

$pageTitle = 'Přihlášení';
$pageSubtitle = 'Přihlášení do administrace';
$cardClass = 'admin-card admin-card--small';

require_once __DIR__ . '/app/includes/header.php';
?>

<?php if (isset($errorMessages[$error])): ?>

    <div class="admin-message admin-message--error">
        <?= htmlspecialchars($errorMessages[$error]) ?>
    </div>

<?php endif; ?>

<form action="/login_process.php" method="post" class="admin-form">
<input type="hidden" name="auth_form_token" value="<?= htmlspecialchars($_SESSION['auth_form_token'],ENT_QUOTES,'UTF-8') ?>">

    <div class="admin-field">
        <label for="username">Uživatelské jméno</label>

        <input
            type="text"
            id="username"
            name="username"
            required
            autocomplete="username"
        >
    </div>

    <div class="admin-field">
        <label for="password">Heslo</label>

        <div class="password-wrapper">

    <input
        type="password"
        id="password"
        name="password"
        required
        autocomplete="current-password"
    >

    <button
        type="button"
        class="password-toggle"
        id="togglePassword"
        aria-label="Zobrazit heslo">
        👁
    </button>

</div>
		
    </div>

    <div class="admin-actions">
        <button type="submit" class="btn">
            Přihlásit se
        </button>
    </div>

</form>

<div class="login-forgot-password">
    <a href="/forgot_password.php">
        Zapomenuté heslo?
    </a>
</div>

<?php
require_once __DIR__ . '/app/includes/footer.php';