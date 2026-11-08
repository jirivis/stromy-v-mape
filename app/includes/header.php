<?php

/*
---------------------------------------------------
 Stromy v mapě
 Společná hlavička administrace
---------------------------------------------------
*/

if (!defined('APP_NAME')) {
    exit('Chybí konfigurace aplikace.');
}

$pageTitle = $pageTitle ?? APP_NAME;
$pageSubtitle = $pageSubtitle ?? '';
$cardClass = $cardClass ?? 'admin-card';
$showVersion = $showVersion ?? true;
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars(APP_NAME) ?>
    </title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <link rel="stylesheet" href="/assets/css/admin_style.css">
	<script src="/assets/js/admin.js" defer></script>
</head>

<body class="admin-page">

<div class="admin-wrapper">
    <main class="<?= htmlspecialchars($cardClass) ?>">

        <div class="admin-brand">
            <img
                src="/favicon.svg"
                class="admin-logo"
                alt="<?= htmlspecialchars(APP_NAME) ?>"
            >

            <h1 class="admin-brand-title">
                <?= htmlspecialchars(APP_NAME) ?>
            </h1>

            <?php if ($pageSubtitle !== ''): ?>
                <p class="admin-subtitle">
                    <?= htmlspecialchars($pageSubtitle) ?>
                </p>
            <?php endif; ?>

            <?php if ($showVersion): ?>
                <p class="admin-version">
                    Verze <?= htmlspecialchars(APP_VERSION) ?>
                </p>
            <?php endif; ?>
        </div>