<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/map/public/project_tools/page_access.php';
svmResumePageAfterLogin();
require_once __DIR__ . '/config_app.php';
require_once __DIR__ . '/map/public/api/db.php';

$username = $_SESSION['username'] ?? 'Uživatel';
$role = $_SESSION['role'] ?? 'viewer';
$isSuperAdmin = (int)($_SESSION['is_super_admin'] ?? 0);

$roleLabels = [
    'admin'  => 'Administrátor',
    'editor' => 'Editor',
    'client' => 'Zákazník',
    'viewer' => 'Prohlížení'
];

$roleLabel = $roleLabels[$role] ?? 'Uživatel';

if ($isSuperAdmin === 1) {
    $roleLabel = 'Správce systému';
}

$pageTitle = 'Dashboard';
$pageSubtitle = 'Administrace systému';
$cardClass = 'admin-card admin-card--wide';

$activeProjectId =
    (int) (
        $_SESSION['active_project_id']
        ?? 0
    );

$activeProjectName =
    $_SESSION['active_project_name']
    ?? '';

$projectError =
    $_GET['project_error']
    ?? '';

$projectErrorMessages = [
    'invalid' =>
        'Nebyl vybrán platný projekt.',

    'forbidden' =>
        'K tomuto projektu nemáte přístup.',

    'server' =>
        'Projekt se nepodařilo aktivovat.'
];

/*
 * Načtení dostupných projektů
 * pro jejich zobrazení na dashboardu.
 */
$userId =
    (int) $_SESSION['user_id'];

if ($isSuperAdmin === 1) {

    $projectStmt = $mysqli->prepare(
        'SELECT
            id,
            name,
            "admin" AS project_role
         FROM projects
         ORDER BY name, id'
    );

} else {

    $projectStmt = $mysqli->prepare(
        'SELECT DISTINCT
            p.id,
            p.name,
            CASE
                WHEN p.owner_id = ?
                    THEN "owner"
                ELSE COALESCE(
                    pu.project_role,
                    "viewer"
                )
            END AS project_role
         FROM projects p
         LEFT JOIN project_users pu
            ON pu.project_id = p.id
            AND pu.user_id = ?
         WHERE
            p.owner_id = ?
            OR pu.user_id = ?
         ORDER BY p.name, p.id'
    );

    $projectStmt->bind_param(
        'iiii',
        $userId,
        $userId,
        $userId,
        $userId
    );
}

$projects = [];

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

$_SESSION['dashboard_csrf'] ??= bin2hex(random_bytes(32));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['dashboard_csrf'], $_POST['token'])) { http_response_code(403); exit('Obnovte dashboard a vyberte projekt znovu.'); }
    $chosen = null;
    foreach ($projects as $candidate) if ((string)$candidate['id'] === (string)($_POST['project_id'] ?? '')) $chosen = $candidate;
    if (!$chosen) { http_response_code(403); exit('K vybranému projektu nemáte přístup.'); }
    $_SESSION['active_project_id'] = (int)$chosen['id'];
    $_SESSION['active_project_name'] = $chosen['name'];
    $_SESSION['active_project_role'] = $chosen['project_role'];
    header('Location: /dashboard.php', true, 303); exit;
}
$mayManageStructures = $isSuperAdmin === 1;
foreach ($projects as $candidate) if (in_array($candidate['project_role'], ['owner','admin'], true)) $mayManageStructures = true;
if($isSuperAdmin===1) $_SESSION['update_csrf']??=bin2hex(random_bytes(32));
require_once __DIR__ . '/app/includes/header.php';
?>

<div class="admin-message admin-message--success">
    Přihlášen jako
    <strong><?= htmlspecialchars($username) ?></strong>
    — <?= htmlspecialchars($roleLabel) ?>
</div>

<?php if (isset($projectErrorMessages[$projectError])): ?>

    <div class="admin-message admin-message--error">
        <?= htmlspecialchars(
            $projectErrorMessages[$projectError]
        ) ?>
    </div>

<?php endif; ?>

<?php if ($activeProjectId > 0): ?>

    <div class="admin-message admin-message--success">

        Aktivní projekt:

        <strong>
            <?= htmlspecialchars(
                $activeProjectName
            ) ?>
        </strong>

    </div>

<?php endif; ?>

<?php
$dashboardProjectRole = '';
foreach ($projects as $dashboardProject) {
    if ((int)$dashboardProject['id'] === $activeProjectId) $dashboardProjectRole = $dashboardProject['project_role'];
}
?>
<style>#projectPicker .admin-actions{margin:16px 0 0;display:flex;justify-content:flex-start}#projectPicker .btn,.dashboard-map-action .btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;box-sizing:border-box;white-space:normal}.dashboard-map-action{margin-top:12px;clear:both}</style>
<?php if($isSuperAdmin===1): ?><p id="updateNotice" hidden data-token="<?=htmlspecialchars($_SESSION['update_csrf'])?>"><span></span><a href="/updates/">Zobrazit změny a možnosti instalace</a></p><script src="/updates/dashboard.js" defer></script><?php endif; ?>
<div class="admin-grid">
    <div class="admin-menu-card">

        <h3>Vybrat projekt</h3>

        <?php if (!$projects): ?>

            <p>
                K uživatelskému účtu zatím
                není přiřazen žádný projekt.
            </p>

        <?php else: ?>

            <?php if(!empty($_SESSION['svm_return_after_login'])): ?><p role="status">Pro návrat na původní stránku vyberte její projekt.</p><?php endif; ?>
            <form
                action="/dashboard.php" id="projectPicker"
                method="post"
                class="admin-form"
            >

                <input type="hidden" name="token" value="<?=htmlspecialchars($_SESSION['dashboard_csrf'])?>"><div class="admin-field">

                    <label for="project_id">
                        Aktivní projekt
                    </label>

                    <select
                        id="project_id"
                        name="project_id"
                        required
                    >

                        <?php if ($activeProjectId < 1): ?>

                            <option value="">
                                — vyberte projekt —
                            </option>

                        <?php endif; ?>

                        <?php foreach ($projects as $project): ?>

                            <option
                                value="<?= (int) $project['id'] ?>"
                                <?= (
                                    (int) $project['id']
                                    === $activeProjectId
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars(
                                    $project['name']
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="admin-actions">

                    <button
                        type="submit"
                        class="btn"
                    >
                        Načíst projekt
                    </button>

                </div>

            </form>
            <?php if($activeProjectId > 0): ?><div class="dashboard-map-action" id="dashboardMapAction"><a href="/map/public/" class="btn">Otevřít mapu</a></div><?php endif; ?>

        <?php endif; ?>

    </div>
	
	<a
    href="/projects.php"
    class="admin-menu-card"
>
    <h3>Projekty</h3>

    <p>
        Správa projektů,
        jejich uživatelů a oprávnění.
    </p>
</a>

<a
    href="/clients.php"
    class="admin-menu-card"
>
    <h3>Zákazníci</h3>

    <p>
        Správa zadavatelů a jejich kontaktních údajů.
    </p>
</a>

<?php if ($isSuperAdmin === 1): ?>

<a
    href="/users.php"
    class="admin-menu-card"
>
    <h3>Uživatelé</h3>

    <p>
        Správa uživatelských účtů
        a přístupových oprávnění.
    </p>
</a>

    <?php endif; ?>

<?php
function dashboardTool(string $href,string $title,string $text,bool $enabled,string $reason='Nejprve vyberte projekt'):void {
 echo $enabled ? '<a class="admin-menu-card" href="'.htmlspecialchars($href).'">' : '<div class="admin-menu-card" aria-disabled="true" style="opacity:.6" title="'.htmlspecialchars($reason).'">';
 echo '<h3>'.htmlspecialchars($title).'</h3><p>'.htmlspecialchars($text).'</p>'.($enabled?'':'<small>'.htmlspecialchars($reason).'</small>').($enabled?'</a>':'</div>');
}
dashboardTool('/map/public/project_tools/table.php','Tabulka projektu','Stromy a plochy: přehled, filtrování, úpravy a výběr k exportu.',$activeProjectId>0);
dashboardTool('/map/public/project_tools/export.php','Export / Import','Excel, QGIS, fotografie, přehledka a import CSV/TXT či GPKG.',in_array($dashboardProjectRole,['owner','admin','editor'],true),$activeProjectId?'Vyžaduje oprávnění editora':'Nejprve vyberte projekt');
if($mayManageStructures) dashboardTool('/map/public/project_tools/templates.php','Moje struktury formulářů','Evidence a úpravy vlastních struktur pro stromy a plochy.',true);
if(in_array($dashboardProjectRole,['owner','admin'],true)){
 require_once __DIR__.'/app/public_map.php';$newReports=0;if(pmReady($mysqli))$newReports=(int)pmQuery($mysqli,"SELECT COUNT(*) AS n FROM svm_public_reports WHERE project_id=? AND status='new'",[$activeProjectId])->get_result()->fetch_assoc()['n'];
 dashboardTool('/map/public/project_tools/public_settings.php','Veřejná mapa a podněty'.($newReports?' ('.$newReports.' nových)':''),'Zveřejněné objekty, výběr údajů a podněty obyvatel.',true);
}
dashboardTool('/map/public/project_tools/settings.php','Nastavení projektu','Vlastní výběr polí nebo uložená struktura pro všechny uživatele projektu.',in_array($dashboardProjectRole,['owner','admin'],true),$activeProjectId?'Vyžaduje oprávnění správce projektu':'Nejprve vyberte projekt');
?>


    <?php if ($isSuperAdmin === 1): ?>

        <div class="admin-menu-card">
            <h3>Nastavení systému</h3><p><a href="/updates/">Aktualizace aplikace</a></p>
            <p>Globální nastavení aplikace a její konfigurace.</p>
        </div>

    <?php endif; ?>

</div>
<script>
const picker=document.getElementById('project_id'),mapAction=document.querySelector('#dashboardMapAction a');
if(picker&&mapAction){const active=picker.value;picker.addEventListener('change',()=>{const changed=picker.value!==active;mapAction.setAttribute('aria-disabled',String(changed));mapAction.style.opacity=changed?'.5':'';mapAction.title=changed?'Nejprve načtěte vybraný projekt':'';});mapAction.addEventListener('click',e=>{if(picker.value!==active){e.preventDefault();document.querySelector('#projectPicker button').focus();}});}
</script>

<div class="admin-actions">
    <a href="/logout.php" class="btn btn-secondary">
        Odhlásit se
    </a>
</div>
<?php
require_once __DIR__ . '/app/includes/footer.php';