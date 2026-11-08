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

    $x = filter_input(
        INPUT_GET,
        'x',
        FILTER_VALIDATE_FLOAT
    );

    $y = filter_input(
        INPUT_GET,
        'y',
        FILTER_VALIDATE_FLOAT
    );

    if (
        $x === false ||
        $y === false ||
        $x === null ||
        $y === null
    ) {

        throw new InvalidArgumentException(
            'Neplatné S-JTSK souřadnice.'
        );
    }


    $proj4 = new Proj4php();

    /*
     * Stejná definice EPSG:5514 jako
     * ve wgs84_to_sjtsk.php.
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

    $projSJT =
        new Proj('EPSG:5514', $proj4);

    $projWGS =
        new Proj('EPSG:4326', $proj4);


    /*
     * Opačný postup než ve
     * wgs84_to_sjtsk.php.
     *
     * Tam ukládáme:
     *
     * DB X = pointSJT->y
     * DB Y = pointSJT->x
     *
     * Proto při převodu zpět:
     *
     * point.x = DB Y
     * point.y = DB X
     */
    $pointSJT =
        new Point(
            (float) $y,
            (float) $x
        );


    $pointWGS =
        $proj4->transform(
            $projSJT,
            $projWGS,
            $pointSJT
        );


    /*
     * WGS84:
     *
     * x = longitude
     * y = latitude
     */
    $lng =
        (float) $pointWGS->x;

    $lat =
        (float) $pointWGS->y;


    /*
     * Základní kontrola výsledku.
     */
    if (
        !is_finite($lat) ||
        !is_finite($lng) ||
        $lat < -90 ||
        $lat > 90 ||
        $lng < -180 ||
        $lng > 180
    ) {

        throw new RuntimeException(
            'Převod S-JTSK → WGS84 vrátil neplatné souřadnice.'
        );
    }


    echo json_encode(
        [
            'success' => true,

            'lat' =>
                round($lat, 7),

            'lng' =>
                round($lng, 7)
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