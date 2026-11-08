<?php
require_once __DIR__ . "/api/db.php";

$q = $_GET['q'] ?? '';
$q = "%$q%";

$stmt = $mysqli->prepare("
    SELECT name
    FROM cultivars
    WHERE name LIKE ?
    ORDER BY name
    LIMIT 20
");

$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row['name'];
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
