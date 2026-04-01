<?php
// api/get_reports.php
header('Content-Type: application/json');
require '../config/db_connect.php';

// 1. Kunin ang Total na Pumasok na Pera (Total Collections)
$sql_paid = "SELECT SUM(amount_paid) as total_collection FROM payments";
$result_paid = $conn->query($sql_paid);
$row_paid = $result_paid->fetch_assoc();
$total_collection = $row_paid['total_collection'] ? $row_paid['total_collection'] : 0;

// 2. Kunin ang Total na Utang pa ng mga Estudyante (Total Receivables)
$sql_unpaid = "SELECT SUM(total_amount) as total_receivables FROM invoices WHERE status != 'Paid'";
$result_unpaid = $conn->query($sql_unpaid);
$row_unpaid = $result_unpaid->fetch_assoc();
$total_receivables = $row_unpaid['total_receivables'] ? $row_unpaid['total_receivables'] : 0;

// 3. I-send ang data bilang JSON para mabasa ng system ng Group 5
echo json_encode([
    "system" => "Group 3 Finance",
    "status" => "success",
    "data" => [
        "total_collection" => floatval($total_collection),
        "total_receivables" => floatval($total_receivables)
    ]
]);
?>