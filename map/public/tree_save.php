<?php

declare(strict_types=1);

function treeSaveDebug(string $message): void
{
    $line =
        date('Y-m-d H:i:s')
        . ' | '
        . $message
        . PHP_EOL;

    @file_put_contents(
        sys_get_temp_dir() . '/stromy_tree_save_debug.log',
        $line,
        FILE_APPEND | LOCK_EX
    );
}

// Return a safe diagnostic code instead of an empty HTTP 500 response.
set_exception_handler(static function (Throwable $error): void {
    global $mysqli;
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        try { $mysqli->rollback(); } catch (Throwable $rollbackError) {}
    }
    error_log('Tree save failed: ' . $error->getMessage());
    $code = $error instanceof mysqli_sql_exception ? (int) $error->getCode() : 0;
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Uložení stromu selhalo.' . ($code ? ' Kód databázové chyby: ' . $code . '.' : '') . ' Zkuste to znovu; pokud chyba trvá, předejte tuto zprávu správci.'
    ], JSON_UNESCAPED_UNICODE);
});

treeSaveDebug('START tree_save.php');

// Ověření přihlášení, aktivního projektu
// a přístupu uživatele k projektu.
require_once __DIR__ . '/../../auth_api.php';
treeSaveDebug('AUTH API OK');

requireProjectRole([
    'owner',
    'admin',
    'editor'
]);

treeSaveDebug('PROJECT ROLE OK');

// auth_api.php už databázi načítá,
// ale ponecháme kompatibilitu s tímto souborem.
require_once __DIR__ . '/api/db.php';

treeSaveDebug('DB INCLUDE OK');

treeSaveDebug(
    'DB CONNECTION | errno='
    . $mysqli->connect_errno
);

$mysqli->set_charset('utf8mb4');

/*
 * Ukládat mohou pouze vlastník,
 * administrátor nebo editor projektu.
 *
 * Superadministrátor dostává v auth_api.php
 * projektovou roli admin.
 */
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

    echo 'K úpravám tohoto projektu nemáte oprávnění.';
    exit;
}
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// 2) Zpracování jen při POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// ID je 0 při založení nového stromu.
// Při editaci přijde ze skrytého pole formuláře.
$tree_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$is_edit = $tree_id > 0;

// Project and version guard shared with the project table.
function treeSaveReject(string $message,int $status=409):never {
 global $mysqli;$mysqli->rollback();http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode(['success'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;
}
if ((int)($_POST['project_id']??0)!==(int)$activeProjectId || !hash_equals($_SESSION['tree_form_csrf']??'',(string)($_POST['tree_form_token']??'')) || empty($_SESSION['tree_form_csrf'])) treeSaveReject('Platnost formuláře nebo aktivní projekt se změnil. Obnovte formulář.',403);
// Validate every uploaded file before changing any tree data.
$validatedPhotoExtensions=[];
if(isset($_FILES['photos'])){
 $files=$_FILES['photos'];
 if(!is_array($files['name']??null)||!is_array($files['error']??null)||count($files['name'])>50)treeSaveReject('Neplatný seznam fotografií.',422);
 foreach($files['name'] as $i=>$unused){
  $error=$files['error'][$i]??null;
  if($error===UPLOAD_ERR_NO_FILE)continue;
  $tmp=$files['tmp_name'][$i]??null;
  if($error!==UPLOAD_ERR_OK||!is_string($tmp)||!is_uploaded_file($tmp))treeSaveReject('Fotografii se nepodařilo přijmout.',422);
  $size=filesize($tmp);$info=@getimagesize($tmp);$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
  $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
  if(!$size||$size>5*1024*1024||!$info||!$ext||($info['mime']??'')!==$mime||$info[0]*$info[1]>24000000)treeSaveReject('Použijte JPG, PNG nebo WebP do 5 MB a 24 megapixelů.',422);
  $validatedPhotoExtensions[$i]=$ext;
 }
}
$mysqli->begin_transaction();
if($is_edit){
 $lock=$mysqli->prepare('SELECT * FROM trees WHERE id=? AND project_id=? FOR UPDATE');$lock->bind_param('ii',$tree_id,$activeProjectId);$lock->execute();$lockedTree=$lock->get_result()->fetch_assoc();if(!$lockedTree)treeSaveReject('Strom nebyl nalezen.',404);
 ksort($lockedTree);$version=hash('sha256',json_encode($lockedTree,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
 if(!hash_equals($version,(string)($_POST['row_version']??'')))treeSaveReject('Strom mezitím změnil jiný uživatel. Vaše změny nebyly uloženy. Otevřete aktuální záznam.');
 $preserve=json_decode($_POST['preserve_fields']??'[]',true);$catalog=json_decode(file_get_contents(__DIR__.'/project_tools/tree_fields.json'),true);
 if(is_array($preserve))foreach($preserve as $key)if(is_string($key)&&isset($catalog[$key])&&array_key_exists($key,$lockedTree)&&!in_array($key,['usr_id','lat_wgs84','lng_wgs84','x_sjtsk','y_sjtsk'])){
  // An explicit autocomplete choice updates the complete taxon, including hidden fields.
  if(($_POST['taxonomy_selected']??'')==='1' && in_array($key,['species','czech','scientific_author','angio_gymno'],true) && array_key_exists($key,$_POST))continue;
  if(preg_match('/^treatment_u[0-3]$/',$key)){$_POST[$key]=[];$_POST[$key.'_custom']=$lockedTree[$key]??'';}else $_POST[$key]=$lockedTree[$key]??'';
 }
}
$number=filter_var($_POST['usr_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($number===false)treeSaveReject('Zadejte kladné celé číslo stromu.',422);
$dupe=$mysqli->prepare('SELECT id FROM trees WHERE project_id=? AND usr_id=? AND id<>? FOR UPDATE');$dupe->bind_param('iii',$activeProjectId,$number,$tree_id);$dupe->execute();if($dupe->get_result()->fetch_assoc())treeSaveReject('Toto číslo stromu je již použité.',422);

// 3) Načtení hodnot z formuláře (minimální sanitizace)
$usr_id            = (int)($_POST['usr_id'] ?? 0);
$species           = trim($_POST['species'] ?? '');
$cultivated_variety= trim($_POST['cultivated_variety'] ?? '');
$czech             = trim($_POST['czech'] ?? '');
$scientific_author = trim($_POST['scientific_author'] ?? '');
$angio_gymno       = trim($_POST['angio_gymno'] ?? '');

$dbh1_cm           = $_POST['dbh1_cm'] !== '' ? (float)$_POST['dbh1_cm'] : null;
$dbh2_cm           = $_POST['dbh2_cm'] !== '' ? (float)$_POST['dbh2_cm'] : null;
$dbh3_cm           = $_POST['dbh3_cm'] !== '' ? (float)$_POST['dbh3_cm'] : null;
$dbh4_cm           = $_POST['dbh4_cm'] !== '' ? (float)$_POST['dbh4_cm'] : null;

// --- NOVÉ OBVODY ---
$girth1_cm         = $_POST['girth1_cm'] !== '' ? (float)$_POST['girth1_cm'] : null;
$girth2_cm         = $_POST['girth2_cm'] !== '' ? (float)$_POST['girth2_cm'] : null;
$girth3_cm         = $_POST['girth3_cm'] !== '' ? (float)$_POST['girth3_cm'] : null;
$girth4_cm         = $_POST['girth4_cm'] !== '' ? (float)$_POST['girth4_cm'] : null;
// -------------------

$height            = $_POST['height'] !== '' ? (float)$_POST['height'] : null;
$canopy_h_m        = $_POST['canopy_h_m'] !== '' ? (float)$_POST['canopy_h_m'] : null;
$spread_m          = $_POST['spread_m'] !== '' ? (float)$_POST['spread_m'] : null;

$phy_age           = $_POST['phy_age'] !== '' ? (int)$_POST['phy_age'] : null;
$ageInput=trim((string)($_POST['age']??''));
if($ageInput!==''&&(!ctype_digit($ageInput)||(float)$ageInput>32767)){treeSaveReject('Stáří musí být celé číslo od 0 do 32767 let.',422);}
$age=$ageInput===''?null:(int)$ageInput;
$vitality          = (isset($_POST['vitality']) && $_POST['vitality'] !== '') ? $_POST['vitality'] : null;
$tree_health       = (isset($_POST['tree_health']) && $_POST['tree_health'] !== '') ? $_POST['tree_health'] : null;
$stability         = (isset($_POST['stability']) && $_POST['stability'] !== '') ? $_POST['stability'] : null;
$operational_safety= (isset($_POST['operational_safety']) && $_POST['operational_safety'] !== '') ? $_POST['operational_safety'] : null;
$longevity         = (isset($_POST['longevity']) && $_POST['longevity'] !== '') ? $_POST['longevity'] : null;
$ecological_value  = (isset($_POST['ecological_value']) && $_POST['ecological_value'] !== '') ? $_POST['ecological_value'] : null;
$landscape_value   = $_POST['landscape_value'] !== '' ? (int)$_POST['landscape_value'] : null;
$note_tree         = trim($_POST['note_tree'] ?? '');

// Pomocná funkce pro ošetření (spojí zaškrtnuté checkboxy a vlastní text do jednoho textu odděleného čárkou)
function processTreatment($checks, $custom) {
    $items = is_array($checks) ? $checks : [];
    if (trim($custom ?? '') !== '') {
        $items[] = trim($custom);
    }
    return empty($items) ? null : implode(', ', $items);
}

$treatment_u0      = processTreatment($_POST['treatment_u0'] ?? [], $_POST['treatment_u0_custom'] ?? '');
$costs_tu0         = $_POST['costs_tu0'] !== '' ? (float)$_POST['costs_tu0'] : null;

$treatment_u1      = processTreatment($_POST['treatment_u1'] ?? [], $_POST['treatment_u1_custom'] ?? '');
$costs_tu1         = $_POST['costs_tu1'] !== '' ? (float)$_POST['costs_tu1'] : null;

$treatment_u2      = processTreatment($_POST['treatment_u2'] ?? [], $_POST['treatment_u2_custom'] ?? '');
$costs_tu2         = $_POST['costs_tu2'] !== '' ? (float)$_POST['costs_tu2'] : null;

$treatment_u3      = processTreatment($_POST['treatment_u3'] ?? [], $_POST['treatment_u3_custom'] ?? '');
$costs_tu3         = $_POST['costs_tu3'] !== '' ? (float)$_POST['costs_tu3'] : null;

$note_treatment    = trim($_POST['note_treatment'] ?? '');
$treatment_history = trim($_POST['treatment_history'] ?? '');

$planted           = $_POST['planted'] ?: null;
$measured_at       = $_POST['measured_at'] ?: null;
$last_insp_at      = $_POST['last_insp_at'] ?: null;
$next_insp_at      = $_POST['next_insp_at'] ?: null;

$normalizeDecimal = static function ($value): ?float {

    if ($value === null) {
        return null;
    }

    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    // Umožní zadání desetinné čárky.
    $value = str_replace(',', '.', $value);

    if (!is_numeric($value)) {
        return null;
    }

    return (float) $value;
};

$lat_wgs84 = $normalizeDecimal(
    $_POST['lat_wgs84'] ?? null
);

$lng_wgs84 = $normalizeDecimal(
    $_POST['lng_wgs84'] ?? null
);

$x_sjtsk = $normalizeDecimal(
    $_POST['x_sjtsk'] ?? null
);

$y_sjtsk = $normalizeDecimal(
    $_POST['y_sjtsk'] ?? null
);

$note_public       = trim($_POST['note_public'] ?? '');

// 4) INSERT nového stromu nebo UPDATE existujícího stromu

$bind_types =
    "isssss" .        // usr_id + 5 textů
    "ddddddddddd" .   // 11 desetinných hodnot
    "iissssssis" .    // phy_age, age + hodnocení + landscape_value + note_tree
    "sdsdsdsd" .      // ošetření a ceny
    "ss" .            // poznámky k ošetření
    "ssss" .          // datumy
    "dddd" .          // souřadnice
    "s";              // veřejná poznámka

if ($is_edit) {

    // Nejprve ověříme, že strom opravdu existuje
    $check_stmt = $mysqli->prepare("
    SELECT id
    FROM trees
    WHERE id = ?
      AND project_id = ?
    LIMIT 1
");

    if (!$check_stmt) {
        die("Prepare check failed: " . $mysqli->error);
    }

    $check_stmt->bind_param(
    'ii',
    $tree_id,
    $activeProjectId
);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if (!$check_result->fetch_assoc()) {
        http_response_code(404);
die(
    'Strom nebyl nalezen v aktivním projektu.'
);
    }

    $check_stmt->close();

    $sql = "
        UPDATE trees SET
            usr_id = ?,
            species = ?,
            cultivated_variety = ?,
            czech = ?,
            scientific_author = ?,
            angio_gymno = ?,

            dbh1_cm = ?,
            dbh2_cm = ?,
            dbh3_cm = ?,
            dbh4_cm = ?,

            girth1_cm = ?,
            girth2_cm = ?,
            girth3_cm = ?,
            girth4_cm = ?,

            height = ?,
            canopy_h_m = ?,
            spread_m = ?,

            phy_age = ?,
            age = ?,
            vitality = ?,
            tree_health = ?,
            stability = ?,
            operational_safety = ?,
            longevity = ?,
            ecological_value = ?,
			landscape_value = ?,
            note_tree = ?,

            treatment_u0 = ?,
            costs_tu0 = ?,
            treatment_u1 = ?,
            costs_tu1 = ?,
            treatment_u2 = ?,
            costs_tu2 = ?,
            treatment_u3 = ?,
            costs_tu3 = ?,

            note_treatment = ?,
            treatment_history = ?,

            planted = ?,
            measured_at = ?,
            last_insp_at = ?,
            next_insp_at = ?,

            lat_wgs84 = ?,
            lng_wgs84 = ?,
            x_sjtsk = ?,
            y_sjtsk = ?,

            note_public = ?

        WHERE id = ?
		AND project_id = ?
		LIMIT 1
    ";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("Prepare UPDATE failed: " . $mysqli->error);
    }

    $update_bind_types =
    $bind_types . 'ii';

    $stmt->bind_param(
        $update_bind_types,

        $usr_id,
        $species,
        $cultivated_variety,
        $czech,
        $scientific_author,
        $angio_gymno,

        $dbh1_cm,
        $dbh2_cm,
        $dbh3_cm,
        $dbh4_cm,

        $girth1_cm,
        $girth2_cm,
        $girth3_cm,
        $girth4_cm,

        $height,
        $canopy_h_m,
        $spread_m,

        $phy_age,
        $age,
        $vitality,
        $tree_health,
        $stability,
        $operational_safety,
        $longevity,
        $ecological_value,
		$landscape_value,
        $note_tree,

        $treatment_u0,
        $costs_tu0,
        $treatment_u1,
        $costs_tu1,
        $treatment_u2,
        $costs_tu2,
        $treatment_u3,
        $costs_tu3,

        $note_treatment,
        $treatment_history,

        $planted,
        $measured_at,
        $last_insp_at,
        $next_insp_at,

        $lat_wgs84,
        $lng_wgs84,
        $x_sjtsk,
        $y_sjtsk,

        $note_public,

        $tree_id,
		$activeProjectId
    );

} else {

    $sql = "
        INSERT INTO trees (
			project_id,
            usr_id,
            species,
            cultivated_variety,
            czech,
            scientific_author,
            angio_gymno,

            dbh1_cm,
            dbh2_cm,
            dbh3_cm,
            dbh4_cm,

            girth1_cm,
            girth2_cm,
            girth3_cm,
            girth4_cm,

            height,
            canopy_h_m,
            spread_m,

            phy_age,
            age,
            vitality,
            tree_health,
            stability,
            operational_safety,
            longevity,
            ecological_value,
			landscape_value,
            note_tree,

            treatment_u0,
            costs_tu0,
            treatment_u1,
            costs_tu1,
            treatment_u2,
            costs_tu2,
            treatment_u3,
            costs_tu3,

            note_treatment,
            treatment_history,

            planted,
            measured_at,
            last_insp_at,
            next_insp_at,

            lat_wgs84,
            lng_wgs84,
            x_sjtsk,
            y_sjtsk,

            note_public
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?
        )
    ";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("Prepare INSERT failed: " . $mysqli->error);
    }

$insertBindTypes =
    'i' . $bind_types;

$stmt->bind_param(
    $insertBindTypes,

    $activeProjectId,
    $usr_id,
        $species,
        $cultivated_variety,
        $czech,
        $scientific_author,
        $angio_gymno,

        $dbh1_cm,
        $dbh2_cm,
        $dbh3_cm,
        $dbh4_cm,

        $girth1_cm,
        $girth2_cm,
        $girth3_cm,
        $girth4_cm,

        $height,
        $canopy_h_m,
        $spread_m,

        $phy_age,
        $age,
        $vitality,
        $tree_health,
        $stability,
        $operational_safety,
        $longevity,
        $ecological_value,
		$landscape_value,
        $note_tree,

        $treatment_u0,
        $costs_tu0,
        $treatment_u1,
        $costs_tu1,
        $treatment_u2,
        $costs_tu2,
        $treatment_u3,
        $costs_tu3,

        $note_treatment,
        $treatment_history,

        $planted,
        $measured_at,
        $last_insp_at,
        $next_insp_at,

        $lat_wgs84,
        $lng_wgs84,
        $x_sjtsk,
        $y_sjtsk,

        $note_public
    );
}

treeSaveDebug('TREE SQL EXECUTE START');

if (!$stmt->execute()) {
    die("Chyba při ukládání stromu: " . $stmt->error);
}

treeSaveDebug(
    'TREE SAVED | id=' . $tree_id
);

// U nového stromu získáme právě vytvořené ID.
// U editace již ID známe.
if (!$is_edit) {
    $tree_id = $stmt->insert_id;
}

treeSaveDebug(
    'TREE ID READY | id=' . $tree_id
);

$stmt->close();

// ---------------------------------------------------------
// 5) ZPRACOVÁNÍ FOTOGRAFIÍ
// ---------------------------------------------------------

treeSaveDebug('PHOTOS START');

// ---------------------------------------------------------
// A) ODPOJENÍ EXISTUJÍCÍCH FOTOGRAFIÍ
//
// Fotografie fyzicky nemažeme.
// Pouze je odebereme z aktuální galerie stromu.
//
// Díky tomu mohou zůstat zachované
// v historických snapshotech.
// ---------------------------------------------------------

$deleted_photo_ids =
    $_POST['deleted_photo_ids']
    ?? [];

if (is_array($deleted_photo_ids)) {

    foreach (
        $deleted_photo_ids
        as $photo_id_raw
    ) {

        $photo_id =
            (int) $photo_id_raw;

        if ($photo_id <= 0) {
            continue;
        }

        $stmt_archive_photo =
            $mysqli->prepare("
                UPDATE trees_photo
                SET status = 'archived'
                WHERE id = ?
                  AND tree_id = ?
                LIMIT 1
            ");

        if (!$stmt_archive_photo) {
            continue;
        }

        $stmt_archive_photo->bind_param(
            'ii',
            $photo_id,
            $tree_id
        );

        $stmt_archive_photo->execute();
        $stmt_archive_photo->close();
    }
}


// ---------------------------------------------------------
// B) ÚPRAVA POPISKŮ A POŘADÍ EXISTUJÍCÍCH FOTOGRAFIÍ
// ---------------------------------------------------------

$existing_photo_ids   = $_POST['existing_photo_ids'] ?? [];
$existing_photo_notes = $_POST['existing_photo_notes'] ?? [];
$existing_photo_sort  = $_POST['existing_photo_sort'] ?? [];

if (is_array($existing_photo_ids)) {

    foreach ($existing_photo_ids as $index => $photo_id_raw) {

        $photo_id = (int) $photo_id_raw;

        if ($photo_id <= 0) {
            continue;
        }

        $note_photo = (string) (
            $existing_photo_notes[$index] ?? ''
        );

        $sort_order = (int) (
            $existing_photo_sort[$index] ?? $index
        );

        $stmt_update_photo = $mysqli->prepare("
			UPDATE trees_photo
			SET
				note_photo = ?,
				sort_order = ?
			WHERE id = ?
			  AND tree_id = ?
			  AND status = 'active'
        ");

        if (!$stmt_update_photo) {
            continue;
        }

        $stmt_update_photo->bind_param(
            "siii",
            $note_photo,
            $sort_order,
            $photo_id,
            $tree_id
        );

        $stmt_update_photo->execute();
        $stmt_update_photo->close();
    }
}


// ---------------------------------------------------------
// C) ULOŽENÍ NOVĚ PŘIDANÝCH FOTOGRAFIÍ
// ---------------------------------------------------------

if (!empty($_FILES['photos']['name'][0])) {

    $upload_dir = __DIR__.'/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $photo_order    = $_POST['photo_order'] ?? [];
    $photo_notes    = $_POST['photo_notes'] ?? [];
    $new_photo_sort = $_POST['new_photo_sort'] ?? [];

    // Nahrané soubory seskupíme podle původního názvu.
    $uploaded_files = [];

    $file_count = count($_FILES['photos']['name']);

    for ($i = 0; $i < $file_count; $i++) {

        if (
            $_FILES['photos']['error'][$i]
            !== UPLOAD_ERR_OK
        ) {
            continue;
        }

        $orig_name = $_FILES['photos']['name'][$i];

        $uploaded_files[$orig_name] = [
            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
            'type'     => $_FILES['photos']['type'][$i] ?? '',
            'size'     => $_FILES['photos']['size'][$i] ?? 0,
            'extension' => $validatedPhotoExtensions[$i]
        ];
    }

    foreach ($photo_order as $index => $ordered_filename) {

        if (!isset($uploaded_files[$ordered_filename])) {
            continue;
        }

        $tmp_name = $uploaded_files[$ordered_filename]['tmp_name'];

        $note_photo = (string) (
            $photo_notes[$index] ?? ''
        );

        $sort_order = (int) (
            $new_photo_sort[$index] ?? $index
        );

        $new_filename = bin2hex(random_bytes(20)).'.'.$uploaded_files[$ordered_filename]['extension'];

        $target_file = $upload_dir . $new_filename;

        if (!move_uploaded_file($tmp_name, $target_file)) {
            continue;
        }

        $stmt_insert_photo = $mysqli->prepare("
            INSERT INTO trees_photo (
                tree_id,
                url,
                note_photo,
                sort_order
            )
            VALUES (?, ?, ?, ?)
        ");

        if (!$stmt_insert_photo) {

            if (is_file($target_file)) {
                unlink($target_file);
            }

            continue;
        }

        $photo_url='uploads/'.$new_filename;
        $stmt_insert_photo->bind_param(
            "issi",
            $tree_id,
            $photo_url,
            $note_photo,
            $sort_order
        );

        $stmt_insert_photo->execute();
        $stmt_insert_photo->close();
    }
}

treeSaveDebug('PHOTOS FINISHED');


// ---------------------------------------------------------
// D) NASTAVENÍ HLAVNÍ FOTOGRAFIE
//
// Hlavní je nyní vždy první fotografie podle sort_order.
// Funguje pro staré i nově přidané fotografie.
// ---------------------------------------------------------

treeSaveDebug('MAIN PHOTO START');

$stmt_main_photo = $mysqli->prepare("
    SELECT url
    FROM trees_photo
    WHERE tree_id = ?
      AND status = 'active'
    ORDER BY sort_order ASC, id ASC
    LIMIT 1
");

$main_photo_url = null;

if ($stmt_main_photo) {

    $stmt_main_photo->bind_param(
        "i",
        $tree_id
    );

    $stmt_main_photo->execute();

    $main_photo_result = $stmt_main_photo->get_result();
    $main_photo_row = $main_photo_result->fetch_assoc();

    $stmt_main_photo->close();
	
	treeSaveDebug('MAIN PHOTO SELECT OK');

    if ($main_photo_row) {
        $main_photo_url = $main_photo_row['url'];
    }
}

$stmt_update_main = $mysqli->prepare("
    UPDATE trees
    SET photo_url = ?
    WHERE id = ?
      AND project_id = ?
");

if ($stmt_update_main) {

    $stmt_update_main->bind_param(
    'sii',
    $main_photo_url,
    $tree_id,
    $activeProjectId
);

    $stmt_update_main->execute();
    $stmt_update_main->close();
}

$mysqli->commit();
treeSaveDebug('MAIN PHOTO UPDATE OK');

treeSaveDebug(
    'FINISH | id=' . $tree_id
);

// ---------------------------------------------------------
// 6) ODPOVĚĎ PO ÚSPĚŠNÉM ULOŽENÍ
// ---------------------------------------------------------

if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'TreeApp'
) {

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        [
            'success' => true,
            'tree_id' => $tree_id,
            'card_url' => 'karta.php?id=' . $tree_id
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}


// Záloha pro klasické odeslání formuláře bez JavaScriptu.
header(
    'Location: karta.php?id=' . $tree_id
);

exit;
