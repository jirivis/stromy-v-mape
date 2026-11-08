<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

try {

    $treeId = filter_input(
        INPUT_GET,
        'tree_id',
        FILTER_VALIDATE_INT
    );

    if (!$treeId || $treeId < 1) {

        http_response_code(400);

        echo json_encode(
            [
                'success' => false,
                'message' => 'Neplatné ID stromu.'
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    $sql = "
        SELECT
            id,
            tree_id,
            measurement_type,
            value_cm,
            measured_at,
            note,
            created_by,
            created_at
        FROM tree_measurements
        WHERE tree_id = ?
        ORDER BY
            CASE
                WHEN measured_at IS NULL THEN 1
                ELSE 0
            END,
            measured_at DESC,
            id DESC
    ";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'Příprava SQL dotazu selhala: ' .
            $mysqli->error
        );
    }

    $stmt->bind_param(
        'i',
        $treeId
    );

    if (!$stmt->execute()) {
        throw new RuntimeException(
            'Provedení SQL dotazu selhalo: ' .
            $stmt->error
        );
    }

    $result = $stmt->get_result();

    $measurements = [];

    while ($row = $result->fetch_assoc()) {
        $measurements[] = $row;
    }

    $stmt->close();

    echo json_encode(
        [
            'success' => true,
            'tree_id' => $treeId,
            'measurements' => $measurements
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (Throwable $error) {

    http_response_code(500);

    error_log(
        'tree_measurements.php: ' .
        $error->getMessage()
    );

    echo json_encode(
        [
            'success' => false,
            'message' => 'Historii měření se nepodařilo načíst.'
        ],
        JSON_UNESCAPED_UNICODE
    );

}