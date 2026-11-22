<?php

require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/project_tools/page_access.php';
svmResumePageAfterLogin();
require_once __DIR__ . '/../../config_app.php';

$username = $_SESSION['username'] ?? 'Uživatel';
$role = $_SESSION['role'] ?? 'viewer';
$isSuperAdmin = (int) ($_SESSION['is_super_admin'] ?? 0);

$topbarTitle = APP_NAME;
$topbarProject =
    $_SESSION['active_project_name']
    ?? '';
$topbarUsername = $username;
$showDashboardLink = true;
$showProjectTableLink = true;
?>

<!DOCTYPE html>
<html lang="cs">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars(APP_NAME) ?></title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >
	
	<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
>

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css"
    >

    <link
        rel="stylesheet"
        href="/assets/css/admin_style.css"
    >

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js">
    </script>

    <script
        src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js">
    </script>

    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js">
    </script>

    <script
        src="js/table_panel_legacy.js?v=2.20"
        defer>
    </script>

    <link rel="stylesheet" href="map_viewport.css?v=20261030">
<link rel="stylesheet" href="areas_map.css?v=2.3">
</head>

<body class="tree-map-page">

<?php
require_once __DIR__ . '/../../app/includes/topbar.php';
?>

<div class="app-workspace">

    <div id="map"></div>

    <section id="tablePanel" class="table-panel">

        <button
            type="button"
            id="toggleTablePanel"
            class="table-panel__handle"
            aria-expanded="true"
            aria-controls="tablePanelContent"
        >
            <span
                class="table-panel__grip"
                aria-hidden="true">
            </span>

            <span class="table-panel__label">
                Seznam stromů
            </span>

            <span
                id="tablePanelArrow"
                class="table-panel__arrow"
                aria-hidden="true">
                ▼
            </span>
        </button>

        <div
            id="tablePanelContent"
            class="table-panel__content"
        >

            <?php if (in_array($_SESSION['active_project_role'] ?? '', ['owner', 'admin', 'editor'], true) || $isSuperAdmin): ?>
            <p style="margin: 8px 12px;">
                <a href="/map/public/project_tools/export.php">↓ Export projektu (tabulka, mapa, fotografie)</a>
            </p>
            <?php endif; ?>

            <table
                id="tabulka"
                class="display"
                style="width: 100%;"
            >

                <thead>
                    <tr>
                        <th>Č. stromu v projektu</th>
                        <th>Vědecké jméno</th>
                        <th>Český název</th>
                        <th>Obvod (cm)</th>
                        <th>Výška (m)</th>
						<th>Fy. věk</th>
                        <th>Vitalita</th>
                        <th>Zdrav. stav</th>
                        <th>Stabilita</th>
						<th>Perspektiva</th>
                        <th>Biol. hodnota</th>
						<th>Sad. hodnota</th>
						<th>Plán péče</th>
                        <th>Fotky</th>
                    </tr>
                </thead>

                <tbody>
                </tbody>

            </table>

        </div>

    </section>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script
    src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js">
</script>

<script>
window.TreeAppUser = {
    projectId: <?= (int)($_SESSION['active_project_id'] ?? 0) ?>,
    userId: <?= (int)($_SESSION['user_id'] ?? 0) ?>,
    projectRole: <?= json_encode(
        $_SESSION['active_project_role']
        ?? 'viewer'
    ) ?>,

    isSuperAdmin: <?= (
        (int) (
            $_SESSION['is_super_admin']
            ?? 0
        )
    ) ?>
};
</script>

<script src="/map/public/js/app.js"></script>
<script src="/map/public/js/map.js?v=2.24.2"></script>
<script src="/map/public/js/selection.js?v=2.25.5"></script>
<script src="/map/public/js/popup.js?v=2.24.2"></script>
<script src="/map/public/js/table.js?v=2.24.2"></script>
<script src="/map/public/js/editor.js?v=project2.8"></script>
<script src="/map/public/js/markers.js?v=20261122"></script>
<script src="/map/public/js/project_filters.js?v=20261118-tabs"></script>
<script src="/map/public/js/filters.js"></script>
<link rel="stylesheet" href="/map/public/advanced_filters.css?v=2.24.3">
<script src="/map/public/js/advanced_filters.js?v=2.25.5"></script>
<script src="/map/public/js/trees.js"></script>
<script src="/map/public/js/areas_layer.js?v=2.25.5"></script>
<script src="/map/public/js/edit_mode.js?v=2.25.5"></script>
<script src="/map/public/main.js"></script>

</body>
</html>



