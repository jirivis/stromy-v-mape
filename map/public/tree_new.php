<?php

declare(strict_types=1);
require_once __DIR__ . '/project_tools/page_access.php';
svmRequirePageLogin();

require_once __DIR__ . '/../../auth_api.php';

requireProjectRole([
    'owner',
    'admin',
    'editor'
]);

require_once __DIR__ . '/api/db.php';

header('Content-Type: text/html; charset=utf-8');
$_SESSION['tree_form_csrf'] ??= bin2hex(random_bytes(32));

$mysqli->set_charset('utf8mb4');

$allowedProjectRoles = [
    'owner',
    'admin',
    'editor'
];

if (
    !in_array(
        $activeProjectRole,
        $allowedProjectRoles,
        true
    )
) {
    http_response_code(403);

    die(
        'K úpravám tohoto projektu nemáte oprávnění.'
    );
}

// ---------------------------------------------------------
// REŽIM FORMULÁŘE
//
// tree_new.php          = nový strom
// tree_new.php?id=24    = editace stromu s databázovým ID 24
// ---------------------------------------------------------

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $id > 0;

$tree = [];
$treePhotos = [];

// Souřadnice předané například z mapy při zakládání stromu
$lat = isset($_GET['lat']) ? trim($_GET['lat']) : '';
$lng = isset($_GET['lng']) ? trim($_GET['lng']) : '';

// ---------------------------------------------------------
// NAČTENÍ EXISTUJÍCÍHO STROMU
// ---------------------------------------------------------

if ($is_edit) {

    $stmt = $mysqli->prepare("
        SELECT *
        FROM trees
        WHERE id = ?
          AND project_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        die(
            'Chyba při přípravě načtení stromu: '
            . htmlspecialchars(
                $mysqli->error,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    $stmt->bind_param(
        'ii',
        $id,
        $activeProjectId
    );

    if (!$stmt->execute()) {
        die(
            'Chyba při načítání stromu: '
            . htmlspecialchars(
                $stmt->error,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    $result = $stmt->get_result();
    $tree = $result->fetch_assoc();

    $stmt->close();

    if (!$tree) {
        http_response_code(404);

        die(
            'Strom nebyl nalezen v aktivním projektu.'
        );
    }

    $photoStmt = $mysqli->prepare("
        SELECT
    tp.id,
    tp.url,
    tp.note_photo,
    tp.sort_order,

    EXISTS (
        SELECT 1
        FROM tree_history_photos thp
        WHERE thp.photo_id = tp.id
    ) AS in_history

FROM trees_photo tp

INNER JOIN trees t
    ON t.id = tp.tree_id

WHERE tp.tree_id = ?
  AND t.project_id = ?
  AND tp.status = 'active'

ORDER BY
    tp.sort_order,
    tp.id
    ");

    if (!$photoStmt) {
        die(
            'Chyba při přípravě načtení fotografií: '
            . htmlspecialchars(
                $mysqli->error,
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }

    $photoStmt->bind_param(
        'ii',
        $id,
        $activeProjectId
    );

    $photoStmt->execute();

    $photoResult =
        $photoStmt->get_result();

    while (
        $photo =
            $photoResult->fetch_assoc()
    ) {
        $treePhotos[] = $photo;
    }

    $photoStmt->close();

    $lat =
        $tree['lat_wgs84'] ?? '';

    $lng =
        $tree['lng_wgs84'] ?? '';
}

$treeHistory = [];

if ($is_edit) {

    $historyStmt = $mysqli->prepare(
        'SELECT
            h.id,
            h.snapshot_at,
            h.tree_data,
            h.event_type,
            h.history_note,
            h.snapshot_by_user_id,
            u.username
         FROM tree_history h
         LEFT JOIN users u
            ON u.id = h.snapshot_by_user_id
         WHERE h.tree_id = ?
           AND h.project_id = ?
         ORDER BY
            h.snapshot_at DESC,
            h.id DESC'
    );

    if ($historyStmt) {

        $historyStmt->bind_param(
            'ii',
            $id,
            $activeProjectId
        );

        $historyStmt->execute();

        $historyResult =
            $historyStmt->get_result();

        while (
            $historyRow =
                $historyResult->fetch_assoc()
        ) {
            $treeHistory[] =
                $historyRow;
        }

        $historyStmt->close();
    }
}

$page_title = $is_edit
    ? 'Upravit strom'
    : 'Přidat nový strom';

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
        <?= htmlspecialchars(
            $page_title,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<link rel="stylesheet" href="style.css">

<style>

    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 20px auto;
        padding: 10px;
    }

    h2 {
        margin-top: 40px;
        border-bottom: 2px solid #ccc;
        padding-bottom: 5px;
    }

    label {
        display: block;
        margin-top: 12px;
        font-weight: bold;
    }

    input,
    select,
    textarea {
        width: 100%;
        padding: 6px;
        margin-top: 4px;
        box-sizing: border-box;
    }

    textarea {
        height: 80px;
    }

    .submit-btn {
        margin-top: 30px;
        padding: 12px 20px;
        font-size: 16px;
        background: #2d7d2d;
        color: white;
        border: none;
        cursor: pointer;
    }

    .submit-btn:hover {
        background: #256525;
    }


    /* =========================================
       Evidence stromu
       ========================================= */

    .tree-record-actions {
        margin-top: 70px;
        padding-top: 22px;
        padding-bottom: 20px;
        border-top: 1px solid #d7ddd8;
        text-align: left;
    }

    .tree-record-status {
        margin: 0 0 14px 0;
    }

    .tree-record-action-title {
        margin-bottom: 14px;
        font-weight: 600;
        color: #555;
    }

    .tree-remove-btn {
        display: inline-flex;
        align-items: center;
        gap: 12px;

        width: auto;

        padding: 10px 15px;

        border: 1px solid #aeb8b0;
        border-radius: 5px;

        background: #f7f9f7;
        color: #344438;

        font-weight: 600;
        text-decoration: none;
    }

    .tree-remove-btn:hover {
        background: #edf2ee;
        border-color: #7f9183;
    }

    .tree-remove-icon {
        display: block;
        width: 36px;
        height: 36px;
        object-fit: contain;
        flex: 0 0 36px;
    }
	
	.tree-history-list {
    margin-top: 32px;
}

.tree-history-list h4 {
    margin: 0 0 14px;
    color: #333;
    font-size: 18px;
}

.tree-history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;

    margin-bottom: 10px;
    padding: 12px 14px;

    border: 1px solid #d7ddd8;
    border-radius: 5px;

    background: #f8faf8;
}

.tree-history-item__main {
    min-width: 0;
}

.tree-history-item__note {
    margin-top: 4px;
    color: #333;
}

.tree-history-item__meta {
    margin-top: 4px;

    color: #777;
    font-size: 12px;
}

.tree-history-open {
    flex: 0 0 auto;

    padding: 7px 11px;

    border: 1px solid #2d7d2d;
    border-radius: 4px;

    color: #2d6f3a;
    text-decoration: none;
    font-weight: 600;
}

.tree-history-open:hover {
    background: #edf5ef;
}

.tree-save-message {
    margin: 18px 0 24px;
    padding: 12px 16px;

    border: 1px solid #9bc5a4;
    border-radius: 5px;

    background: #edf7ef;
    color: #245c31;

    font-weight: 600;
}

/* =========================================
   Dialog odebrání fotografie
   ========================================= */

.photo-history-dialog[hidden] {
    display: none;
}

.photo-history-dialog {
    position: fixed;
    inset: 0;
    z-index: 10000;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 20px;
}

.photo-history-dialog__backdrop {
    position: absolute;
    inset: 0;

    background: rgba(0, 0, 0, 0.42);
}

.photo-history-dialog__window {
    position: relative;
    z-index: 1;

    width: min(520px, 100%);
    padding: 24px;

    border-radius: 7px;

    background: #ffffff;

    box-shadow:
        0 12px 40px
        rgba(0, 0, 0, 0.22);
}

.photo-history-dialog__title {
    margin: 0 0 12px;

    color: #2f4935;
    font-size: 20px;
}

.photo-history-dialog__message {
    margin: 0;

    color: #444;
    line-height: 1.55;
}

.photo-history-dialog__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;

    margin-top: 22px;
}

.photo-history-dialog__actions button {
    width: auto;
    margin-top: 0;
}

/* =========================================
   Nebezpečná zóna
   ========================================= */

.tree-danger-zone {
    margin-top: 70px;
    padding: 22px;

    border: 1px solid #d98282;
    border-radius: 6px;

    background: #fff7f7;
}

.tree-danger-zone h3 {
    margin: 0 0 14px;

    color: #9c2020;
}

.tree-danger-zone p {
    margin: 8px 0;

    color: #555;
    line-height: 1.5;
}

.tree-delete-permanent-btn {
    display: inline-flex;
    align-items: center;

    margin-top: 16px;
    padding: 11px 16px;

    border: 1px solid #b71c1c;
    border-radius: 5px;

    background: #c62828;
    color: #fff;

    font-weight: 700;
    text-decoration: none;
}

.tree-delete-permanent-btn:hover {
    background: #a91d1d;
    border-color: #951717;
}

/* =========================================
   Fotografie stromu
   ========================================= */

.photo-upload-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.photo-grid {
    display: grid;
    grid-template-columns: repeat(
        auto-fill,
        minmax(180px, 1fr)
    );
    gap: 14px;
    margin-top: 16px;

    height: auto !important;
    max-height: none !important;
    overflow: visible !important;
}

.photo-item {
    position: relative;
    min-width: 0;
    padding: 8px;

    height: auto !important;
    max-height: none !important;
    overflow: visible !important;

    border: 1px solid #d7ded8;
    border-radius: 7px;
    background: #fff;
}

.photo-item img {
    display: block;
    width: 100%;
    height: auto;
    max-height: 320px;

    object-fit: contain;
    border-radius: 5px;
}

.photo-note-input {
    width: 100%;
    min-height: 60px;
    margin-top: 8px;

    box-sizing: border-box;
    resize: vertical;
}

.photo-controls {
    display: flex;
    gap: 8px;

    width: 100%;
    margin-top: 8px;
}

.photo-btn {
    display: block;

    flex: 1 1 0;
    width: auto;
    min-width: 0;
    min-height: 42px;

    margin: 0;
    padding: 8px;

    border: 1px solid #aeb9b0;
    border-radius: 5px;

    background: #f2f5f2;
    color: #173821;

    font-size: 18px;
    font-weight: 700;
    cursor: pointer;
}

.photo-btn:disabled {
    opacity: 0.35;
    cursor: default;
}

.photo-remove {
    position: absolute;
    top: 12px;
    right: 12px;

    width: 34px;
    height: 34px;

    margin: 0;
    padding: 0;

    border: 0;
    border-radius: 50%;

    background: rgba(0, 0, 0, 0.7);
    color: #fff;

    font-size: 24px;
    line-height: 34px;
    cursor: pointer;
}


/* Mobil */
@media (max-width: 600px) {

    .photo-grid {
        display: block;
    }

    .photo-item {
        width: 100%;
        box-sizing: border-box;
        margin-bottom: 16px;
    }

    .photo-controls {
        gap: 10px;
        margin-top: 10px;
    }

    .photo-btn {
        min-height: 48px;
        font-size: 20px;
    }
}

</style>

<link rel="stylesheet" href="project_tools/form_theme.css?v=2.25.9"><style>.svm-history-entry{border:1px solid #ccd8ce;border-radius:6px;padding:12px;margin:8px 0}.svm-history-entry summary{cursor:pointer;font-weight:600}.svm-history-table{border-collapse:collapse;width:100%;margin:12px 0}.svm-history-table th,.svm-history-table td{text-align:left;border-bottom:1px solid #dde4de;padding:7px;vertical-align:top;overflow-wrap:anywhere}.svm-history-table th{width:38%}</style><style>
#tree-save-actions{margin:24px 0}#tree-photos{border-top:1px solid #cedbcf;padding-top:18px;margin-top:20px}#tree-photos>h2{margin:0 0 15px}
#tree-photos .compact-photo-button{display:inline-flex!important;width:auto!important;min-width:0!important;padding:12px 18px!important;margin:0 10px 12px 0!important;background:#28613d!important;color:#fff!important;border:1px solid #90ab92;border-radius:6px;font-weight:500!important}
#tree-history{border-top:1px solid #cedbcf;margin-top:28px;padding-top:18px}#tree-history>h3{margin-top:0}.tree-record-actions{margin:32px 0 20px;padding-top:24px;border-top:1px solid #cedbcf}.tree-danger-zone{margin-top:24px}.photo-meta-save{margin:16px 0}.photo-item textarea{max-width:100%}
.tree-card-link{display:inline-block;padding:10px 16px;border:1px solid #28613d;border-radius:6px;background:#28613d;color:#fff;text-decoration:none;font-weight:600}
</style></head>

<body>
<nav style="margin:12px 0"><a href="/dashboard.php" target="_top" style="display:inline-block;padding:8px 12px;border:1px solid #9aac9a;border-radius:6px;color:#235536;background:#edf4ea;text-decoration:none">← Dashboard</a></nav>

<h1>
    <?= htmlspecialchars(
        $page_title,
        ENT_QUOTES,
        'UTF-8'
    ) ?>
</h1>
<?php if ($is_edit): ?>
<p><a class="tree-card-link" href="karta.php?id=<?= (int) $id ?>" target="_blank" rel="noopener">Otevřít kartu ↗</a></p>
<?php endif; ?>

<?php if (
    isset($_GET['history_saved'])
    && $_GET['history_saved'] === '1'
): ?>

    <div class="tree-save-message">
        ✓ Stav stromu byl uložen do historie.
    </div>

<?php endif; ?>

<form
    action="tree_save.php"
    method="POST"
    enctype="multipart/form-data"
>

    <input type="hidden" name="project_id" value="<?= (int)$activeProjectId ?>">
    <input type="hidden" name="tree_form_token" value="<?= htmlspecialchars($_SESSION['tree_form_csrf']) ?>">
    <input type="hidden" name="row_version" value="<?php $versionTree=$tree;ksort($versionTree);echo hash('sha256',json_encode($versionTree,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)); ?>">
    <?php if ($is_edit): ?>

        <input
            type="hidden"
            name="id"
            value="<?= (int) $id ?>"
        >

    <?php endif; ?>

    <?php require __DIR__ . "/tree_form/sections/identification.php"; ?>

    <?php require __DIR__ . "/tree_form/sections/dendrometry.php"; ?>

    <?php require __DIR__ . "/tree_form/sections/assessment.php"; ?>

    <?php require __DIR__ . "/tree_form/sections/treatments.php"; ?>

    <?php require __DIR__ . "/tree_form/sections/timestamps.php"; ?>

    <?php require __DIR__ . "/tree_form/sections/location.php"; ?>

<div id="tree-save-actions" class="tools"></div>
<div id="tree-photos"><h2>Fotografie</h2>
    <?php require __DIR__ . "/tree_form/sections/photos.php"; ?>
</div>

<?php if ($is_edit): ?>

    <div class="tree-record-actions">

        <?php if (
            ($tree['status'] ?? 'active') === 'removed'
        ): ?>

            <p class="tree-record-status">
                Tento záznam je veden jako
                <strong>odstraněný strom</strong>.
            </p>

            <a
                href="tree_restore.php?id=<?= (int) $id ?>"
                class="submit-btn"
            >
                Obnovit strom
            </a>

<?php else: ?>

    <a
        href="tree_remove.php?id=<?= (int) $id ?>"
        class="tree-remove-btn"
    >
        <img
            src="/icons/stump.svg"
            alt=""
            class="tree-remove-icon"
        >

        <span>
            Označit strom jako odstraněný
        </span>
    </a>

<?php endif; ?>

    </div>

<?php endif; ?>

</form>


<?php if ($is_edit): ?>

<div
    class="tree-history-actions"
    id="tree-history"
>

        <h3>
            Historie stromu
        </h3>

        <?php require __DIR__.'/tree_form/history_compare.php'; ?>

        <?php if (
            isset($_GET['history_saved'])
            && $_GET['history_saved'] === '1'
        ): ?>

            <p class="tree-history-success">
                Stav stromu byl uložen do historie.
            </p>

        <?php endif; ?>

        <p>
            Uložte současný stav stromu
            jako historický záznam.
        </p>

        <form
            action="tree_history_save.php"
            method="POST"
        >
            <input type="hidden" name="tree_form_token" value="<?= htmlspecialchars($_SESSION['tree_form_csrf'],ENT_QUOTES,'UTF-8') ?>">

            <input
                type="hidden"
                name="tree_id"
                value="<?= (int) $id ?>"
            >
			
			<input
				type="hidden"
				id="history-return-to"
				name="return_to"
				value=""
			>

            <label for="history_note">
                Poznámka k historickému záznamu
            </label>

            <input
                type="text"
                id="history_note"
                name="history_note"
                maxlength="500"
                placeholder="např. Pravidelná kontrola 2026"
            >

            <button
                type="submit"
                class="submit-btn"
            >
                Uložit stav do historie
            </button>
			
        </form>
		
		<?php if ($treeHistory): ?>

    <div class="tree-history-list">

        <h4>
            Historické záznamy
            (<?= count($treeHistory) ?>)
        </h4>

        <?php foreach (
            $treeHistory
            as $historyItem
        ): ?>

            <details class="svm-history-entry">
              <summary><?= htmlspecialchars((string)$historyItem['snapshot_at'], ENT_QUOTES, 'UTF-8') ?> · Uložil: <?= htmlspecialchars((string)($historyItem['username'] ?: ('uživatel #'.$historyItem['snapshot_by_user_id'])), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string)($historyItem['history_note'] ?: 'Historický záznam'), ENT_QUOTES, 'UTF-8') ?></summary>
              <?php
              $historyData=json_decode((string)$historyItem['tree_data'],true);
              $historyFields=json_decode(file_get_contents(__DIR__.'/project_tools/tree_fields.json'),true);
              $historyFields=['usr_id'=>['label'=>'Číslo stromu (usr_id)']]+$historyFields+['status'=>['label'=>'Stav']];
              ?>
              <?php if(is_array($historyData)): ?><table class="svm-history-table"><tbody>
              <?php foreach($historyFields as $key=>$meta): $value=$historyData[$key]??null;if($value===null||$value==='')continue;$value=$meta['options'][(string)$value]??$value; ?>
                <tr><th><?= htmlspecialchars($meta['label'],ENT_QUOTES,'UTF-8') ?></th><td><?= nl2br(htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8')) ?></td></tr>
              <?php endforeach; ?></tbody></table><?php else: ?><p>Podrobnosti záznamu nelze přečíst.</p><?php endif; ?>
              <a href="tree_history_view.php?id=<?= (int)$historyItem['id'] ?>">Otevřít historickou kartu</a>
            </details>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

    </div>

<?php endif; ?>

<?php if ($is_edit): ?>

    <div class="tree-danger-zone">

        <h3>
            Nebezpečná zóna
        </h3>

        <p>
            Trvalé smazání použijte pouze
            pro chybně založený, duplicitní
            nebo testovací záznam.
        </p>

        <p>
            Pro běžné vyřazení stromu z evidence
            použijte
            <strong>Označit strom jako odstraněný</strong>.
        </p>

        <a
            href="tree_delete.php?id=<?= (int) $id ?>"
            class="tree-delete-permanent-btn"
        >
            Trvale smazat strom
        </a>

    </div>

<?php endif; ?>

<div
    id="photo-history-dialog"
    class="photo-history-dialog"
    hidden
>
    <div
        class="photo-history-dialog__backdrop"
        data-photo-dialog-cancel
    ></div>

    <div
        class="photo-history-dialog__window"
        role="dialog"
        aria-modal="true"
        aria-labelledby="photo-history-dialog-title"
    >

        <h3
            id="photo-history-dialog-title"
            class="photo-history-dialog__title"
        >
            Odebrání fotografie
        </h3>

        <p
            id="photo-history-dialog-message"
            class="photo-history-dialog__message"
        ></p>

        <div class="photo-history-dialog__actions">

            <button
                type="button"
                id="photo-history-save-first"
                class="submit-btn"
            >
                Uložit stav do historie
            </button>

            <button
                type="button"
                id="photo-history-remove"
                class="tree-remove-btn"
            >
                Odpojit fotografii
            </button>

<button
    type="button"
    id="photo-history-cancel"
    class="btn btn-secondary"
    data-photo-dialog-cancel
>
    Zrušit
</button>

        </div>

    </div>
</div>


<script>

    window.treeFormMode = {
        isEdit: <?= $is_edit ? 'true' : 'false' ?>,
        treeId: <?= (int) $id ?>
    };
	
window.treeHistorySavedForPhoto =
    <?= (
        isset($_GET['history_saved_for_photo'])
        && $_GET['history_saved_for_photo'] === '1'
    ) ? 'true' : 'false' ?>;	

window.loadedTree = <?= json_encode(
    [
        'tree' => $tree,
        'photos' => $treePhotos
    ],
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) ?>;

</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="js/project_context_map.js?v=2.25.9"></script>
<?php require __DIR__ . "/tree_form/scripts.php"; ?>

<script>window.FormFieldsBoot={kind:'trees',project_id:<?= (int)$activeProjectId ?>};</script>
<script src="project_tools/visibility.js?v=2.3"></script>
<script src="project_tools/editor_exit.js?v=2.25.9"></script><script>
(()=>{const gallery=document.getElementById('tree-photos'),form=document.querySelector('form[action="tree_save.php"]'),bar=document.getElementById('tree-save-actions');if(!gallery||!form||!bar)return;
const save=gallery.querySelector('button[type="submit"],input[type="submit"],button.submit-btn');if(save)bar.append(save);
const record=form.querySelector('.tree-record-actions'),history=document.getElementById('tree-history');if(record)(history||form).after(record);
gallery.querySelectorAll('label[for="photo-input"],label[for="photo-camera-input"]').forEach(label=>label.classList.add('compact-photo-button'));
})();
</script></body>

</html>