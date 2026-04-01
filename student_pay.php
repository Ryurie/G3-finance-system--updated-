<?php
require 'config/db_connect.php'; 

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["receipt"])) {
    $name = $_POST['student_name'];
    $amount = $_POST['amount'];
    // Kukunin natin yung piniling Term ng estudyante
    $term = $_POST['payment_term']; 
    $ref_no = $_POST['ref_no'];
    
    $target_dir = "uploads/";
    $file_extension = pathinfo($_FILES["receipt"]["name"], PATHINFO_EXTENSION);
    $new_filename = uniqid("RECEIPT_") . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $target_file)) {
        // Isasama na natin yung payment_term sa isesave sa database
        $sql = "INSERT INTO online_payments (student_name, amount_paid, payment_term, reference_number, receipt_image) 
                VALUES ('$name', '$amount', '$term', '$ref_no', '$new_filename')";
        
        if ($conn->query($sql) === TRUE) {
            $message = "<div class='success-msg'>Payment for $term submitted successfully! Please wait for admin approval.</div>";
        }
    } else {
        $message = "<div class='error-msg'>Error uploading receipt. Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Online Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .upload-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #111827; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 8px; color: #374151; }
        input[type="text"], input[type="number"], input[type="file"], select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        button { width: 100%; padding: 12px; background-color: #10b981; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        button:hover { background-color: #059669; }
        .success-msg { background: #d1fae5; color: #065f46; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="upload-card">
        <h2>Upload Proof of Payment</h2>
        <p style="text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 25px;">Installment & Term Payments</p>
        
        <?php echo $message; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Student Full Name</label>
                <input type="text" name="student_name" required placeholder="e.g. Juan Dela Cruz">
            </div>
            
            <div class="form-group">
                <label>Payment Term (Installment)</label>
                <select name="payment_term" required>
                    <option value="Full Payment">Full Payment</option>
                    <option value="Downpayment">Downpayment</option>
                    <option value="Prelims">Prelims</option>
                    <option value="Midterms">Midterms</option>
                    <option value="Finals">Finals</option>
                </select>
            </div>

            <div class="form-group">
                <label>Amount Paid (₱)</label>
                <input type="number" name="amount" required placeholder="5000">
            </div>
            <div class="form-group">
                <label>Reference Number</label>
                <input type="text" name="ref_no" required placeholder="1234 5678 9101">
            </div>
            <div class="form-group">
                <label>Upload Receipt Image</label>
                <input type="file" name="receipt" accept="image/*" required>
            </div>
            <button type="submit">Submit Payment</button>
        </form>
    </div>
</body>
</html>