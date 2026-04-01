<?php
// Itatakda natin na JSON ang ibabalik nitong file para madaling basahin ng Group 4
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para ma-access ng ibang group kahit iba ang localhost port nila

require '../config/db_connect.php';

if (isset($_GET['student_id']) && !empty($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Kukunin natin ang pinakabagong bayad ng estudyante
    $sql = "SELECT p.payment_id, p.amount_paid, p.payment_method, p.payment_date, i.status as invoice_status
            FROM payments p 
            JOIN invoices i ON p.invoice_id = i.invoice_id 
            WHERE i.student_id = ? 
            ORDER BY p.payment_date DESC LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $payment_data = $result->fetch_assoc();
        
        // Ibabalik natin ito sa Group 4
        echo json_encode([
            "success" => true,
            "message" => "Payment record found.",
            "student_id" => $student_id,
            "latest_payment" => [
                "receipt_no" => "REC-" . str_pad($payment_data['payment_id'], 4, '0', STR_PAD_LEFT),
                "amount_paid" => $payment_data['amount_paid'],
                "method" => $payment_data['payment_method'],
                "date" => $payment_data['payment_date'],
                "current_clearance_status" => $payment_data['invoice_status']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Wala pang record ng bayad ang estudyanteng ito.",
            "student_id" => $student_id
        ]);
    }
    $stmt->close();
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error: Kailangan ng student_id. Halimbawa: ?student_id=2026001"
    ]);
}
?>