<?php

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../auth_api.php';

require_once __DIR__ . '/proj4php/Proj4php.php';
require_once __DIR__ . '/proj4php/common.php';
require_once __DIR__ . '/proj4php/datum.php';
require_once __DIR__ . '/proj4php/longlat.php';
require_once __DIR__ . '/proj4php/point.php';
require_once __DIR__ . '/proj4php/proj.php';
require_once __DIR__ . '/proj4php/wkt.php';

if (file_exists(__DIR__ . '/proj4php/projCode/Krovak.php')) {

    require_once __DIR__ . '/proj4php/projCode/Krovak.php';

} elseif (file_exists(__DIR__ . '/proj4php/Krovak.php')) {

    require_once __DIR__ . '/proj4php/Krovak.php';

} else {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'projection_library_missing'
    ]);

    exit;
}

try {

    $lat = filter_input(
        INPUT_GET,
        'lat',
        FILTER_VALIDATE_FLOAT
    );

    $lng = filter_input(
        INPUT_GET,
        'lng',
        FILTER_VALIDATE_FLOAT
    );

    if ($lat === false || $lng === false ||
        $lat === null || $lng === null) {

        throw new InvalidArgumentException(
            'Neplatné WGS84 souřadnice.'
        );
    }

    /*
     * Základní kontrola, že souřadnice dávají
     * smysl jako WGS84.
     */
    if (
        $lat < -90 ||
        $lat > 90 ||
        $lng < -180 ||
        $lng > 180
    ) {

        throw new InvalidArgumentException(
            'WGS84 souřadnice jsou mimo platný rozsah.'
        );
    }

    $proj4 = new Proj4php();

    /*
     * Stejná definice EPSG:5514,
     * jakou používá trees_geojson.php.
     */
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

    $projWGS =
        new Proj('EPSG:4326', $proj4);

    $projSJT =
        new Proj('EPSG:5514', $proj4);

    /*
     * Proj4 používá:
     *
     * x = longitude
     * y = latitude
     */
    $pointWGS =
        new Point($lng, $lat);

    $pointSJT =
        $proj4->transform(
            $projWGS,
            $projSJT,
            $pointWGS
        );

    /*
     * V databázi aplikace používáme:
     *
     * X = jižní souřadnice
     * Y = západní souřadnice
     *
     * tedy běžný zápis S-JTSK:
     * X ≈ -1 0xx xxx
     * Y ≈   -7xx xxx
     *
     * Proj4php vrací osy v pořadí,
     * které zde převedeme na naše DB pole.
     */
    $xSjtsk = (float) $pointSJT->y;
    $ySjtsk = (float) $pointSJT->x;

    /*
     * Aplikace ukládá záporné hodnoty.
     */
    if ($xSjtsk > 0) {
        $xSjtsk *= -1;
    }

    if ($ySjtsk > 0) {
        $ySjtsk *= -1;
    }

    echo json_encode(
        [
            'success' => true,

            'x_sjtsk' =>
                round($xSjtsk, 3),

            'y_sjtsk' =>
                round($ySjtsk, 3)
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {

    http_response_code(
        $e instanceof InvalidArgumentException
            ? 400
            : 500
    );

    echo json_encode(
        [
            'success' => false,
            'message' => $e->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );
}