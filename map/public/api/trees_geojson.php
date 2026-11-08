<?php

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

/*
---------------------------------------------------
 Stromy v mapě
 GeoJSON stromů
---------------------------------------------------
*/

header('Content-Type: application/geo+json; charset=utf-8');

require_once __DIR__ . '/../../../auth_api.php';
require_once __DIR__ . '/db.php';

// Knihovna Proj4php
require_once __DIR__ . '/proj4php/Proj4php.php';
require_once __DIR__ . '/proj4php/common.php';
require_once __DIR__ . '/proj4php/datum.php';
require_once __DIR__ . '/proj4php/longlat.php';
require_once __DIR__ . '/proj4php/point.php';
require_once __DIR__ . '/proj4php/proj.php';
require_once __DIR__ . '/proj4php/wkt.php';

/*
 * Soubor s podporou Křovákova zobrazení.
 */
if (file_exists(__DIR__ . '/proj4php/projCode/Krovak.php')) {
    require_once __DIR__ . '/proj4php/projCode/Krovak.php';

} elseif (file_exists(__DIR__ . '/proj4php/Krovak.php')) {
    require_once __DIR__ . '/proj4php/Krovak.php';

} else {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'projection_library_missing',
        'message' => 'Na serveru chybí podpora Křovákova zobrazení.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/*
 * db.php může používat proměnnou $mysqli nebo $conn.
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
        'message' => 'Databázové připojení není dostupné.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $db->set_charset('utf8mb4');

    /*
     * Inicializace převodu S-JTSK → WGS84.
     */
    $proj4 = new Proj4php();

    $proj4->addDef(
        'EPSG:5514',
        '+proj=krovak '
        . '+lat_0=49.5 '
        . '+lon_0=24.83333333333333 '
        . '+alpha=30.28813972222222 '
        . '+k=0.9999 '
        . '+x_0=0 '
        . '+y_0=0 '
        . '+ellps=bessel '
        . '+towgs84=570.8,85.7,462.8,4.998,1.587,5.261,3.56 '
        . '+units=m '
        . '+no_defs'
    );

    $projSJT = new Proj('EPSG:5514', $proj4);
    $projWGS = new Proj('EPSG:4326', $proj4);

    /*
     * Fotografie už načítáme samostatně, proto zde není potřeba JOIN.
     */
    $treeStatement =
    $db->prepare(
        'SELECT *
         FROM trees
         WHERE project_id = ?
         ORDER BY usr_id, id'
    );

if (!$treeStatement) {
    throw new RuntimeException(
        'Dotaz stromů nelze připravit: '
        . $db->error
    );
}

$treeStatement->bind_param(
    'i',
    $activeProjectId
);

$treeStatement->execute();

$result =
    $treeStatement->get_result();

$photoStatement = $db->prepare("
    SELECT
        url,
        note_photo
    FROM trees_photo
    WHERE tree_id = ?
      AND status = 'active'
    ORDER BY sort_order ASC, id ASC
");

    $features = [];

    while ($row = $result->fetch_assoc()) {
        $treeId = (int) ($row['id'] ?? 0);

        /*
         * Galerie fotografií.
         */
        $gallery = [];

        $photoStatement->bind_param('i', $treeId);
        $photoStatement->execute();

        $photoResult = $photoStatement->get_result();

        while ($photo = $photoResult->fetch_assoc()) {
            $gallery[] = [
                'url' => $photo['url'] ?? null,
                'note_photo' => $photo['note_photo'] ?? null
            ];
        }

        $photoResult->free();

        /*
         * Nejdříve zkusíme použít uložené WGS84 souřadnice.
         */
        $lat = null;
        $lon = null;

        $dbLat = isset($row['lat_wgs84']) && $row['lat_wgs84'] !== ''
            ? (float) $row['lat_wgs84']
            : null;

        $dbLon = isset($row['lng_wgs84']) && $row['lng_wgs84'] !== ''
            ? (float) $row['lng_wgs84']
            : null;

        if (
            $dbLat !== null
            && $dbLon !== null
            && $dbLat >= 48
            && $dbLat <= 51
            && $dbLon >= 12
            && $dbLon <= 19
        ) {
            $lat = $dbLat;
            $lon = $dbLon;

        } elseif (
            $dbLat !== null
            && $dbLon !== null
            && $dbLat >= 12
            && $dbLat <= 19
            && $dbLon >= 48
            && $dbLon <= 51
        ) {
            // Prohozené hodnoty latitude a longitude.
            $lat = $dbLon;
            $lon = $dbLat;
        }

        /*
         * Pokud WGS84 není dostupné, převedeme S-JTSK.
         */
        $xSjtsk = isset($row['x_sjtsk']) && $row['x_sjtsk'] !== ''
            ? (float) $row['x_sjtsk']
            : null;

        $ySjtsk = isset($row['y_sjtsk']) && $row['y_sjtsk'] !== ''
            ? (float) $row['y_sjtsk']
            : null;

        if (($lat === null || $lon === null) && $xSjtsk !== null && $ySjtsk !== null) {
            try {
                if ($xSjtsk > 0) {
                    $xSjtsk *= -1;
                }

                if ($ySjtsk > 0) {
                    $ySjtsk *= -1;
                }

                /*
                 * GeoJSON používá pořadí longitude, latitude.
                 * Proj4php zde dostává pořadí Y, X.
                 */
                $pointSJT = new Point($ySjtsk, $xSjtsk);
                $pointWGS = $proj4->transform($projSJT, $projWGS, $pointSJT);

                $lon = (float) $pointWGS->x;
                $lat = (float) $pointWGS->y;

            } catch (Throwable $e) {
                error_log(
                    'S-JTSK konverze selhala pro strom ID '
                    . $treeId
                    . ': '
                    . $e->getMessage()
                );
            }
        }

        /*
         * Strom bez platných souřadnic do mapy neposíláme.
         * Je to lepší než zobrazovat ho v Guinejském zálivu na [0, 0].
         */
        if (
            $lat === null
            || $lon === null
            || !is_finite($lat)
            || !is_finite($lon)
            || $lat < -90
            || $lat > 90
            || $lon < -180
            || $lon > 180
        ) {
            error_log(
                'Strom ID '
                . $treeId
                . ' nemá platné souřadnice a nebyl přidán do GeoJSON.'
            );

            continue;
        }

        $features[] = [
            'type' => 'Feature',

            'geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    $lon,
                    $lat
                ]
            ],

            'properties' => [
                'id' => $treeId,
				'project_id' => $activeProjectId,
                'usr_id' => (int) ($row['usr_id'] ?? 0),

                'species' => $row['species'] ?? null,
                'czech' => $row['czech'] ?? null,

                'dbh1_cm' => (int) ($row['dbh1_cm'] ?? 0),
                'dbh2_cm' => (int) ($row['dbh2_cm'] ?? 0),
                'dbh3_cm' => (int) ($row['dbh3_cm'] ?? 0),
                'dbh4_cm' => (int) ($row['dbh4_cm'] ?? 0),
                'girth1_cm' => (float) ($row['girth1_cm'] ?? 0),
                'girth2_cm' => (float) ($row['girth2_cm'] ?? 0),
                'girth3_cm' => (float) ($row['girth3_cm'] ?? 0),
                'girth4_cm' => (float) ($row['girth4_cm'] ?? 0),

                'height' => (float) ($row['height'] ?? 0),
                'canopy_h_m' => (float) ($row['canopy_h_m'] ?? 0),
                'spread_m' => (float) ($row['spread_m'] ?? 0),

                'phy_age' => (int) ($row['phy_age'] ?? 0),
                'vitality' => (int) ($row['vitality'] ?? 0),
				'tree_health' => (int) ($row['tree_health'] ?? 0),
				'stability' => (int) ($row['stability'] ?? 0),

				/*
				 * Životní cyklus stromu.
				 */
				'status' => $row['status'] ?? 'active',
				'removed_at' => $row['removed_at'] ?? null,
				'removal_type' => $row['removal_type'] ?? null,
				'removal_reason' => $row['removal_reason'] ?? null,

				'longevity' => $row['longevity'] ?? null,
                'ecological_value' => $row['ecological_value'] ?? null,
				'landscape_value' => $row['landscape_value'] ?? null,

                'note_tree' => $row['note_tree'] ?? null,

				'treatment_u0' => $row['treatment_u0'] ?? null,
'costs_tu0' => (
    isset($row['costs_tu0']) &&
    $row['costs_tu0'] !== ''
)
    ? (int) $row['costs_tu0']
    : null,

'treatment_u1' => $row['treatment_u1'] ?? null,
'costs_tu1' => (
    isset($row['costs_tu1']) &&
    $row['costs_tu1'] !== ''
)
    ? (int) $row['costs_tu1']
    : null,

'treatment_u2' => $row['treatment_u2'] ?? null,
'costs_tu2' => (
    isset($row['costs_tu2']) &&
    $row['costs_tu2'] !== ''
)
    ? (int) $row['costs_tu2']
    : null,

'treatment_u3' => $row['treatment_u3'] ?? null,
'costs_tu3' => (
    isset($row['costs_tu3']) &&
    $row['costs_tu3'] !== ''
)
    ? (int) $row['costs_tu3']
    : null,

'costs_total' => (
    isset($row['costs_total']) &&
    $row['costs_total'] !== ''
)
    ? (int) $row['costs_total']
    : null,

                'note_treatment' => $row['note_treatment'] ?? null,
                'angio_gymno' => $row['angio_gymno'] ?? null,

                'measured_at' => $row['measured_at'] ?? null,
                'last_insp_at' => $row['last_insp_at'] ?? null,
                'next_insp_at' => $row['next_insp_at'] ?? null,
                'planted' => $row['planted'] ?? null,

                'note_public' => $row['note_public'] ?? null,
                'photo_url' => $row['photo_url'] ?? null,
                'gallery' => $gallery
            ]
        ];
    }

    $photoStatement->close();

	$result->free();

	$treeStatement->close();

    echo json_encode([
        'type' => 'FeatureCollection',
        'features' => $features
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);

    error_log('trees_geojson.php: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => 'geojson_generation_failed',
        'message' => 'GeoJSON se nepodařilo vytvořit.'
    ], JSON_UNESCAPED_UNICODE);
}