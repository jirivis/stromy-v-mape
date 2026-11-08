<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . "/api/db.php";

$q = $_GET['q'] ?? '';
$q = "%$q%";

$stmt = $mysqli->prepare("
    SELECT 
        scientific_name, 
        author, 
        czech_name,
        family,
        genus,
        powo_id AS wfo_id
    FROM species 
    WHERE scientific_name LIKE ? 
       OR czech_name LIKE ?
       OR genus LIKE ?
    ORDER BY scientific_name 
    LIMIT 20
");

$stmt->bind_param("sss", $q, $q, $q);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {

    // Ochrana proti NULL hodnotám
    foreach ($row as $key => $value) {
        if ($value === null) {
            $row[$key] = "";
        }
    }

    $data[] = $row;
}

error_log("FAMILY: " . $row['family']);

echo json_encode($data, JSON_UNESCAPED_UNICODE);
