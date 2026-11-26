<?php
require_once __DIR__.'/../../../app/tree_lifecycle.php';

ini_set('display_errors', '1');
error_reporting(E_ALL);

/*
---------------------------------------------------
 Stromy v mapě
 Načtení stromů
---------------------------------------------------
*/

// Same-origin API; no deployment-specific CORS header.
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../../../auth_api.php';
require_once __DIR__ . '/db.php';

/*
 * Podpora připojení pojmenovaného $mysqli nebo $conn.
 */
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $db = $mysqli;
} elseif (isset($conn) && $conn instanceof mysqli) {
    $db = $conn;
} else {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'database_connection_missing',
        'message' => 'Soubor db.php nevytvořil databázové připojení.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $db->set_charset('utf8mb4');

    /*
     * Načtení všech stromů.
     */
    $result = $db->query("
        SELECT *
        FROM trees
    ");

    if (!$result) {
        throw new RuntimeException(
            'Chyba při načítání stromů: ' . $db->error
        );
    }

    /*
     * Načtení všech fotografií jedním dotazem.
     * Fotografie jsou již seřazené podle stromu a sort_order.
     */
    $resultPhotos = $db->query("
        SELECT
            id,
            tree_id,
            url,
            note_photo,
            sort_order
        FROM trees_photo
		WHERE status = 'active'
        ORDER BY tree_id ASC, sort_order ASC, id ASC
    ");

    if (!$resultPhotos) {
        throw new RuntimeException(
            'Chyba při načítání fotografií: ' . $db->error
        );
    }

    /*
     * Galerie seskupené podle tree_id.
     */
    $galleries = [];

    while ($photo = $resultPhotos->fetch_assoc()) {
        $photoTreeId = (int) $photo['tree_id'];

        if (!isset($galleries[$photoTreeId])) {
            $galleries[$photoTreeId] = [];
        }

        $galleries[$photoTreeId][] = [
            'id' => (int) $photo['id'],
            'url' => $photo['url'],
            'note_photo' => $photo['note_photo'] ?? '',
            'sort_order' => (int) $photo['sort_order']
        ];
    }

    $trees = [];

    while ($row = $result->fetch_assoc()) {
        $treeId = (int) ($row['id'] ?? 0);

        /*
         * Galerie konkrétního stromu.
         */
        $gallery = $galleries[$treeId] ?? [];

        /*
         * WGS84 souřadnice.
         */
        $lat = isset($row['lat_wgs84']) && $row['lat_wgs84'] !== ''
            ? (float) $row['lat_wgs84']
            : null;

        $lon = isset($row['lng_wgs84']) && $row['lng_wgs84'] !== ''
            ? (float) $row['lng_wgs84']
            : null;

        /*
         * S-JTSK souřadnice.
         */
        $xSjtsk = isset($row['x_sjtsk']) && $row['x_sjtsk'] !== ''
            ? (float) $row['x_sjtsk']
            : null;

        $ySjtsk = isset($row['y_sjtsk']) && $row['y_sjtsk'] !== ''
            ? (float) $row['y_sjtsk']
            : null;

        $trees[] = [
            'id' => $treeId,
            'usr_id' => (int) ($row['usr_id'] ?? 0),

            'species' => $row['species'] ?? null,
            'czech' => $row['czech'] ?? null,

            'girth_cm' => (int) ($row['girth_cm'] ?? 0),

            'dbh1_cm' => (int) ($row['dbh1_cm'] ?? 0),
            'dbh2_cm' => (int) ($row['dbh2_cm'] ?? 0),
            'dbh3_cm' => (int) ($row['dbh3_cm'] ?? 0),
            'dbh4_cm' => (int) ($row['dbh4_cm'] ?? 0),

            'height' => (float) ($row['height'] ?? 0),
            'canopy_h_m' => (float) ($row['canopy_h_m'] ?? 0),
            'spread_m' => (float) ($row['spread_m'] ?? 0),

            'phy_age' => (int) ($row['phy_age'] ?? 0),
            'vitality' => (int) ($row['vitality'] ?? 0),
            'tree_health' => (int) ($row['tree_health'] ?? 0),
            'stability' => (int) ($row['stability'] ?? 0),
			
			'status' => $row['status'] ?? 'active',
			'removed_at' => $row['removed_at'] ?? null,
			'removal_type' => $row['removal_type'] ?? null,
                'lifecycle_event' => svmTreeEvent($row),
			'removal_reason' => $row['removal_reason'] ?? null,

            'longevity' => $row['longevity'] ?? null,
            'ecological_value' => $row['ecological_value'] ?? null,
            'operational_safety' => $row['operational_safety'] ?? null,

            'note_tree' => $row['note_tree'] ?? null,

            'treatment_u0' => $row['treatment_u0'] ?? null,
            'treatment_u1' => $row['treatment_u1'] ?? null,
            'treatment_u2' => $row['treatment_u2'] ?? null,
            'treatment_u3' => $row['treatment_u3'] ?? null,

            'note_treatment' => $row['note_treatment'] ?? null,
            'angio_gymno' => $row['angio_gymno'] ?? null,

            'planted' => $row['planted'] ?? null,
            'measured_at' => $row['measured_at'] ?? null,
            'last_insp_at' => $row['last_insp_at'] ?? null,
            'next_insp_at' => $row['next_insp_at'] ?? null,

            'lat_wgs84' => $lat,
            'lng_wgs84' => $lon,

            'x_sjtsk' => $xSjtsk,
            'y_sjtsk' => $ySjtsk,

            'note_public' => $row['note_public'] ?? null,

            /*
             * Záložní hlavní fotografie ze sloupce trees.photo_url.
             */
            'photo_url' => $row['photo_url'] ?? null,

            /*
             * Celá galerie ve správném pořadí.
             */
            'gallery' => $gallery
        ];
    }

    echo json_encode(
        $trees,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_PRETTY_PRINT
        | JSON_THROW_ON_ERROR
    );

} catch (Throwable $e) {
    http_response_code(500);

    error_log('get_trees.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}