<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/app/bootstrap.php';
// Stromy v mapě — export aktuálního projektu. PHP 8.2+ / ZipArchive.

ini_set('display_errors', '0');
require_once __DIR__ . '/../../auth_api.php';
requireProjectRole(['owner', 'admin', 'editor']);
require_once __DIR__ . '/api/db.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function exportFail(string $message, int $code = 500): never {
    http_response_code($code);
    header('Content-Type: text/plain; charset=utf-8');
    exit($message);
}
if (!isset($activeProjectId) || (int)$activeProjectId <= 0) {
    exportFail('Nejprve vyberte projekt v administraci.', 409);
}
$projectId = (int)$activeProjectId;
$projectName = (string)($_SESSION['active_project_name'] ?? ('Projekt ' . $projectId));
if (empty($_SESSION['project_export_token'])) {
    $_SESSION['project_export_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['project_export_token'];
$h = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html><html lang="cs"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Export projektu</title>
<style>body{font:17px/1.6 system-ui;background:#f3f6f2;color:#243b2d;margin:0;padding:24px}main{max-width:640px;margin:4vh auto;background:white;padding:28px;border-radius:16px}button{background:#28633e;color:white;border:0;border-radius:8px;padding:12px 20px;font:inherit;cursor:pointer}label{display:block;margin:18px 0}a{color:#28633e}</style>
<main><a href="index.php">← Zpět na mapu</a><h1>Export projektu</h1>
<p><strong><?= $h($projectName) ?></strong></p>
<p>Stáhne všechny stromy projektu včetně odstraněných, bez ohledu na filtry mapy. ZIP obsahuje tabulku CSV pro Excel a body GeoJSON pro QGIS.</p>
<form method="post">
<input type="hidden" name="token" value="<?= $h($token) ?>">
<input type="hidden" name="project_id" value="<?= $projectId ?>">
<label><input type="checkbox" name="photos" value="1" checked> Přidat fotografie do složek podle čísla stromu</label>
<label><input type="checkbox" name="archived" value="1"> Přidat také archivované fotografie do oddělených podsložek</label>
<button type="submit">Stáhnout ZIP</button>
<p>Export s fotografiemi může chvíli trvat. Případné chybějící fotografie budou uvedeny v souboru upozorneni.txt.</p>
</form></main></html>
<?php
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') exportFail('Nepodporovaná metoda.', 405);
if (!is_string($_POST['token'] ?? null) || !hash_equals($token, $_POST['token'])) exportFail('Platnost formuláře vypršela. Obnovte stránku.', 403);
if ((int)($_POST['project_id'] ?? 0) !== $projectId) exportFail('Aktivní projekt se změnil. Obnovte stránku exportu.', 409);
if (!class_exists('ZipArchive')) exportFail('Na serveru není dostupné PHP rozšíření ZipArchive. Pošlete tuto hlášku správci.');
$includePhotos = ($_POST['photos'] ?? '') === '1';
$includeArchived = ($_POST['archived'] ?? '') === '1';
session_write_close();

function exportJson($value): string {
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
}
function exportCsv(array $headers, array $rows): string {
    $f = fopen('php://temp', 'w+b');
    if ($f === false) throw new RuntimeException('CSV stream');
    fwrite($f, "\xEF\xBB\xBF");
    fputcsv($f, $headers, ';', '"', '', "\r\n");
    foreach ($rows as $row) {
        $values = [];
        foreach ($headers as $key) {
            $value = $row[$key] ?? '';
            if (is_string($value) && preg_match('/^[\s]*[=+@-]/u', $value) && !is_numeric($value)) $value = "'" . $value;
            $values[] = $value;
        }
        fputcsv($f, $values, ';', '"', '', "\r\n");
    }
    rewind($f);
    $contents = stream_get_contents($f);
    fclose($f);
    if ($contents === false) throw new RuntimeException('CSV read');
    return $contents;
}
// Přijímáme výhradně místní soubory z uploads. Žádné vzdálené HTTP stahování.
function exportPhotoPath(string $url): ?string {
    $parts = parse_url($url);
    if ($parts === false || isset($parts['user']) || isset($parts['pass'])) return null;
    if (isset($parts['host']) && !in_array(strtolower($parts['host']), [strtolower((string)parse_url(svmUrl(),PHP_URL_HOST))], true)) return null;
    if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) return null;
    $path = rawurldecode($parts['path'] ?? '');
    if (str_contains($path, "\0") || str_contains($path, '\\')) return null;
    if (str_starts_with($path, '/map/public/uploads/')) $path = substr($path, strlen('/map/public/'));
    if (!str_starts_with($path, 'uploads/')) return null;
    $root = realpath(__DIR__ . '/uploads');
    $file = realpath(__DIR__ . '/' . $path);
    if (!$root || !$file || !str_starts_with($file, $root . DIRECTORY_SEPARATOR) || !is_file($file) || !is_readable($file)) return null;
    return $file;
}
$temp = null;
$zip = null;
try {
    $db = $mysqli ?? $conn ?? null;
    if (!$db instanceof mysqli) throw new RuntimeException('Missing database');
    $db->set_charset('utf8mb4');
    $stmt = $db->prepare('SELECT * FROM trees WHERE project_id = ? ORDER BY usr_id, id');
    $stmt->bind_param('i', $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $headers = array_map(static fn($f) => $f->name, $result->fetch_fields());
    $trees = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $sql = 'SELECT p.* FROM trees_photo p JOIN trees t ON t.id = p.tree_id WHERE t.project_id = ?';
    if (!$includeArchived) $sql .= " AND p.status = 'active'";
    $sql .= ' ORDER BY p.tree_id, p.sort_order, p.id';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $projectId);
    $stmt->execute();
    $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $temp = tempnam(sys_get_temp_dir(), 'tree_export_');
    if ($temp === false) throw new RuntimeException('Temporary file');
    register_shutdown_function(static function () use ($temp) { if (is_file($temp)) @unlink($temp); });
    $zip = new ZipArchive();
    if ($zip->open($temp, ZipArchive::OVERWRITE) !== true) throw new RuntimeException('ZIP open');
    $add = static function (string $name, string $contents) use ($zip): void {
        if (!$zip->addFromString($name, $contents)) throw new RuntimeException('ZIP add ' . $name);
    };
    $warnings = [];
    $features = [];
    $folders = [];
    $used = [];
    foreach ($trees as $tree) {
        $label = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)($tree['usr_id'] ?? ''));
        if ($label === '') $label = 'bez_cisla';
        $folder = 'fotografie/strom_' . $label;
        if (isset($used[$folder])) {
            $warnings[] = 'Duplicitní číslo stromu: ' . $label . ', ID ' . $tree['id'];
            $folder .= '_id_' . $tree['id'];
        }
        $used[$folder] = true;
        $folders[(string)$tree['id']] = $folder;
        if ($includePhotos && !$zip->addEmptyDir($folder)) throw new RuntimeException('ZIP directory');
        $lat = $tree['lat_wgs84'] ?? null;
        $lon = $tree['lng_wgs84'] ?? null;
        $valid = is_numeric($lat) && is_numeric($lon) && is_finite((float)$lat) && is_finite((float)$lon) && abs((float)$lat) <= 90 && abs((float)$lon) <= 180 && !((float)$lat === 0.0 && (float)$lon === 0.0);
        if (!$valid) $warnings[] = 'Strom ' . $label . ' (ID ' . $tree['id'] . '): chybí platná poloha WGS84; geometrie je null.';
        $features[] = ['type'=>'Feature', 'id'=>(int)$tree['id'], 'geometry'=>$valid ? ['type'=>'Point','coordinates'=>[(float)$lon,(float)$lat]] : null, 'properties'=>$tree];
    }
    // Záložní hlavní snímek, pokud není zapsaný v galerii.
    $known = [];
    foreach ($photos as $p) $known[(string)$p['tree_id']][(string)$p['url']] = true;
    foreach ($trees as $tree) {
        $url = (string)($tree['photo_url'] ?? '');
        if ($url !== '' && !isset($known[(string)$tree['id']][$url])) {
            $photos[] = ['id'=>'main', 'tree_id'=>$tree['id'], 'url'=>$url, 'note_photo'=>'Hlavní fotografie', 'sort_order'=>0, 'status'=>'active'];
        }
    }
    $photoRows = [];
    $photoCount = 0;
    $totalBytes = 0;
    foreach ($photos as $p) {
        $folder = $folders[(string)$p['tree_id']];
        if (($p['status'] ?? '') !== 'active') $folder .= '/archivovane';
        $file = exportPhotoPath((string)$p['url']);
        $dest = '';
        $state = 'nezahrnuto';
        if ($includePhotos) {
            if ($file === null) {
                $state = 'chybi_nebo_nepodporovana_cesta';
                $warnings[] = 'Fotografie stromu ID ' . $p['tree_id'] . ', foto ID ' . $p['id'] . ': nedostupná nebo nepodporovaná cesta.';
            } else {
                $totalBytes += filesize($file);
                if ($totalBytes > 1024 * 1024 * 1024) throw new RuntimeException('PHOTO_SIZE_LIMIT');
                $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file));
                $dest = $folder . '/' . $p['id'] . '_' . $name;
                if (!$zip->addFile($file, $dest)) throw new RuntimeException('ZIP photo');
                $zip->setCompressionName($dest, ZipArchive::CM_STORE);
                $state = 'zahrnuto';
                $photoCount++;
            }
        }
        $photoRows[] = ['tree_id'=>$p['tree_id'], 'photo_id'=>$p['id'], 'status'=>$p['status'] ?? '', 'sort_order'=>$p['sort_order'] ?? '', 'note_photo'=>$p['note_photo'] ?? '', 'source_url'=>$p['url'], 'export_path'=>$dest, 'export_status'=>$state];
    }
    $add('stromy.csv', exportCsv($headers, $trees));
    $add('stromy.geojson', exportJson(['type'=>'FeatureCollection', 'features'=>$features]));
    $add('fotografie.csv', exportCsv(['tree_id','photo_id','status','sort_order','note_photo','source_url','export_path','export_status'], $photoRows));
    $add('export_info.json', exportJson(['project_id'=>$projectId,'project_name'=>$projectName,'exported_at'=>date(DATE_ATOM),'tree_count'=>count($trees),'photo_count'=>$photoCount,'archived_photos'=>$includeArchived,'warnings'=>count($warnings)]));
    $add('upozorneni.txt', $warnings ? implode("\r\n", $warnings) : 'Bez zjištěných upozornění.');
    $add('CTI_ME.txt', "Export projektu: $projectName\r\nVšechny stromy včetně odstraněných, bez filtrů mapy.\r\nCSV: UTF-8 s BOM, oddělovač středník. Názvy sloupců odpovídají databázi.\r\nGeoJSON: WGS84, pořadí délka/šířka. Chybějící poloha = null, strom zůstává v datech.\r\nPůvodní hodnoty a NULL jsou zachovány v GeoJSON. V CSV NULL odpovídá prázdnému poli.\r\nFotografie: složky dle usr_id; při duplicitě doplněno id. Názvy fotek mají předponu ID fotografie pro zabránění kolizím.\r\nArchivované fotografie jsou oddělené, pokud byly vybrány. Export neobsahuje historické snapshoty.\r\nZkontrolujte upozorneni.txt a fotografie.csv.\r\n");
    if (!$zip->close()) throw new RuntimeException('ZIP close');
    $zip = null;
    clearstatcache(true, $temp);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="projekt_' . $projectId . '_' . date('Ymd_His') . '.zip"');
    header('Content-Length: ' . filesize($temp));
    readfile($temp);
} catch (Throwable $e) {
    if ($zip instanceof ZipArchive) { try { $zip->close(); } catch (Throwable $ignored) {} }
    error_log('project_export.php: ' . $e->getMessage());
    exportFail($e->getMessage() === 'PHOTO_SIZE_LIMIT' ? 'Fotografie přesahují limit 1 GB této verze exportu. Zkuste export bez fotografií a domluvíme rozdělení.' : 'Export se nepodařilo dokončit. Pošlete správci odpovídající záznam z PHP error logu.');
} finally {
    if (is_string($temp) && is_file($temp)) @unlink($temp);
}
