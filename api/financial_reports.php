<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require '../config/db_connect.php';

// 1. Kunin ang Total Collected (Pumasok na pera)
$q_collected = $conn->query("SELECT SUM(amount_paid) as collected FROM payments");
$total_collected = $q_collected->fetch_assoc()['collected'] ?? 0;

// 2. Kunin ang Total Expected (Base Amount + Penalty)
$q_invoiced = $conn->query("SELECT SUM(total_amount + penalty) as expected FROM invoices");
$total_expected = $q_invoiced->fetch_assoc()['expected'] ?? 0;

// 3. I-compute ang Pending / Unpaid Balances ng lahat ng estudyante
$total_pending = $total_expected - $total_collected;
if ($total_pending < 0) $total_pending = 0;

// 4. Bilangin kung ilan na ang fully paid at ilan pa ang may utang
$q_status = $conn->query("SELECT status, COUNT(*) as count FROM invoices GROUP BY status");
$status_breakdown = [];
while ($row = $q_status->fetch_assoc()) {
    $status_breakdown[$row['status']] = $row['count'];
}

// Ibabalik natin ang buong report sa Group 5
echo json_encode([
    "success" => true,
    "report_generated_at" => date("Y-m-d H:i:s"),
    "financial_summary" => [
        "total_collected" => (float)$total_collected,
        "total_pending_balance" => (float)$total_pending,
        "total_expected_revenue" => (float)$total_expected
    ],
    "invoice_statistics" => $status_breakdown
]);
?>