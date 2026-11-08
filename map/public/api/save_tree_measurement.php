<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);

        echo json_encode(
            [
                'success' => false,
                'message' => 'Povolená je pouze metoda POST.'
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    $rawInput = file_get_contents('php://input');

    $data = json_decode(
        $rawInput ?: '',
        true
    );

    if (!is_array($data)) {

        http_response_code(400);

        echo json_encode(
            [
                'success' => false,
                'message' => 'Neplatná vstupní data.'
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    $treeId =
        filter_var(
            $data['tree_id'] ?? null,
            FILTER_VALIDATE_INT
        );

    $measurementType =
        (string) (
            $data['measurement_type'] ?? ''
        );

    $valueCm =
        filter_var(
            $data['value_cm'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

    $measuredAt =
        trim(
            (string) (
                $data['measured_at'] ?? ''
            )
        );

    $note =
        trim(
            (string) (
                $data['note'] ?? ''
            )
        );

    if (!$treeId || $treeId < 1) {
        throw new InvalidArgumentException(
            'Neplatné ID stromu.'
        );
    }

    if (
        !in_array(
            $measurementType,
            ['dbh', 'girth'],
            true
        )
    ) {
        throw new InvalidArgumentException(
            'Neplatný typ měření.'
        );
    }

    if (
        $valueCm === false ||
        $valueCm <= 0 ||
        $valueCm > 10000
    ) {
        throw new InvalidArgumentException(
            'Neplatná hodnota měření.'
        );
    }

    $date =
        DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $measuredAt
        );

    $dateErrors =
        DateTime::getLastErrors();

    if (
        !$date ||
        (
            is_array($dateErrors) &&
            (
                $dateErrors['warning_count'] > 0 ||
                $dateErrors['error_count'] > 0
            )
        )
    ) {
        throw new InvalidArgumentException(
            'Neplatné datum nebo čas měření.'
        );
    }

    if (mb_strlen($note) > 255) {
        throw new InvalidArgumentException(
            'Poznámka je příliš dlouhá.'
        );
    }

    /*
     * Ověření, že strom existuje.
     */
    $treeCheck =
        $mysqli->prepare(
            "
                SELECT id
                FROM trees
                WHERE id = ?
                LIMIT 1
            "
        );

    if (!$treeCheck) {
        throw new RuntimeException(
            'Kontrolu stromu nelze připravit.'
        );
    }

    $treeCheck->bind_param(
        'i',
        $treeId
    );

    $treeCheck->execute();

    $treeResult =
        $treeCheck->get_result();

    if ($treeResult->num_rows === 0) {

        $treeCheck->close();

        http_response_code(404);

        echo json_encode(
            [
                'success' => false,
                'message' => 'Strom nebyl nalezen.'
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    $treeCheck->close();

    /*
     * Přihlášeného uživatele doplníme později.
     */
    $createdBy = null;

    $sql = "
        INSERT INTO tree_measurements
        (
            tree_id,
            measurement_type,
            value_cm,
            measured_at,
            note,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            NULLIF(?, ''),
            ?
        )
    ";

    $stmt =
        $mysqli->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException(
            'SQL dotaz nelze připravit: ' .
            $mysqli->error
        );
    }

    $stmt->bind_param(
        'isdssi',
        $treeId,
        $measurementType,
        $valueCm,
        $measuredAt,
        $note,
        $createdBy
    );

    if (!$stmt->execute()) {
        throw new RuntimeException(
            'Měření se nepodařilo uložit: ' .
            $stmt->error
        );
    }

    $measurementId =
        $stmt->insert_id;

    $stmt->close();

    echo json_encode(
        [
            'success' => true,
            'measurement_id' => $measurementId,
            'message' => 'Měření bylo uloženo.'
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

} catch (InvalidArgumentException $error) {

    http_response_code(422);

    echo json_encode(
        [
            'success' => false,
            'message' => $error->getMessage()
        ],
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $error) {

    http_response_code(500);

    error_log(
        'save_tree_measurement.php: ' .
        $error->getMessage()
    );

    echo json_encode(
        [
            'success' => false,
            'message' => 'Měření se nepodařilo uložit.'
        ],
        JSON_UNESCAPED_UNICODE
    );

}