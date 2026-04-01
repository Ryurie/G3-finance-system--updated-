<?php
require '../../config/db_connect.php';

// I-check kung may pinasang ID sa URL
if (isset($_GET['id'])) {
    $fee_id = intval($_GET['id']);

    // Burahin ang record
    $sql = "DELETE FROM fees WHERE fee_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fee_id);
    $stmt->execute();
    $stmt->close();
}

// Ibalik agad ang user sa listahan ng fees
header("Location: index.php");
exit();
?>