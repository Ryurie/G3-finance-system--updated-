<?php
// api_clearance.php
header('Content-Type: application/json');
require 'db_connect.php';

if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // I-check kung may 'Unpaid' o 'Partial' na invoice ang estudyante
    $sql = "SELECT COUNT(*) as pending_invoices FROM invoices WHERE student_id = ? AND status != 'Paid'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['pending_invoices'] > 0) {
        // May utang pa, block sa enrollment
        echo json_encode([
            "student_id" => $student_id, 
            "clearance_status" => "Blocked", 
            "message" => "Student has unpaid balances."
        ]);
    } else {
        // Fully paid, cleared na
        echo json_encode([
            "student_id" => $student_id, 
            "clearance_status" => "Cleared", 
            "message" => "Student is cleared for enrollment."
        ]);
    }
} else {
    echo json_encode(["error" => "Missing student_id parameter."]);
}
?>