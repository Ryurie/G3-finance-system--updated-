<?php
// add_fee.php - Updated Design
require '../../config/db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fee_name = $_POST['fee_name'];
    $amount = $_POST['amount'];
    $academic_year = $_POST['academic_year'];

    $sql = "INSERT INTO fees (fee_name, amount, academic_year) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $fee_name, $amount, $academic_year);

    if ($stmt->execute()) {
        $message = "<div class='success'>✅ Fee successfully added to the database!</div>";
    } else {
        $message = "<div class='error'>❌ Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Fee - Group 3</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

    <div class="form-container">
        <h2>📚 Add New Fee</h2>
        
        <?php echo $message; ?>

        <form method="POST" action="">
            <label for="fee_name">Fee Name (e.g., Tuition, Lab Fee):</label>
            <input type="text" id="fee_name" name="fee_name" required>

            <label for="amount">Amount (₱):</label>
            <input type="number" id="amount" name="amount" step="0.01" required>

            <label for="academic_year">Academic Year (e.g., 2026-2027):</label>
            <input type="text" id="academic_year" name="academic_year" required>

            <button type="submit" class="btn btn-add" style="width: 100%; margin-top: 25px;">Save Fee Structure</button>
        </form>

        <a href="index.php" class="btn btn-back" style="display: block; text-align: center; margin-top: 15px;">← Back to Fee List</a>
    </div>

</body>
</html>