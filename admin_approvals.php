<?php
session_start();
require 'config/db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action']; 
    
    $new_status = ($action == 'Approve') ? 'Approved' : 'Rejected';
    
    $conn->query("UPDATE online_payments SET status='$new_status' WHERE payment_id='$payment_id'");
    
    $admin_name = $_SESSION['username'] ?? 'Admin';
    $desc = "Online Payment #$payment_id was $new_status.";
    $conn->query("INSERT INTO audit_logs (admin_name, action_type, description) VALUES ('$admin_name', '$action', '$desc')");
    
    header("Location: admin_approvals.php"); 
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Approvals | Finance System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #FAF9F7; color: #111827; margin: 0; padding: 40px 5%; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-back { padding: 10px 20px; background: #111827; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
        
        .card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #E5E7EB; }
        th { color: #6B7280; font-size: 13px; text-transform: uppercase; }
        .receipt-link { color: #3b82f6; text-decoration: none; font-weight: 600; }
        .receipt-link:hover { text-decoration: underline; }
        
        .btn-approve { background: #10b981; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-reject { background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        
        /* Design para sa Term Badge */
        .term-badge { background-color: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-flex">
            <div>
                <h1 style="margin:0;">Pending Online Payments</h1>
                <p style="color: #6B7280; margin-top:5px;">Review installment and full payments submitted by students.</p>
            </div>
            <a href="index.php" class="btn-back">Back to Dashboard</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Date Submitted</th>
                        <th>Student Name</th>
                        <th>Term</th> <th>Amount</th>
                        <th>Ref Number</th>
                        <th>Receipt</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM online_payments WHERE status='Pending' ORDER BY date_uploaded ASC");
                    
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $date = date("M d, Y - h:i A", strtotime($row['date_uploaded']));
                            
                            // Kung luma yung data at walang laman yung payment_term, default sa Full Payment
                            $term_display = !empty($row['payment_term']) ? $row['payment_term'] : 'Full Payment';

                            echo "<tr>
                                    <td style='color: #6B7280; font-size: 14px;'>{$date}</td>
                                    <td><b>{$row['student_name']}</b></td>
                                    <td><span class='term-badge'>{$term_display}</span></td> <td style='color: #10b981; font-weight: bold;'>₱" . number_format($row['amount_paid'], 2) . "</td>
                                    <td>{$row['reference_number']}</td>
                                    <td><a href='uploads/{$row['receipt_image']}' target='_blank' class='receipt-link'>View Image</a></td>
                                    <td>
                                        <form method='POST' style='display:inline;'>
                                            <input type='hidden' name='payment_id' value='{$row['payment_id']}'>
                                            <button type='submit' name='action' value='Approve' class='btn-approve'>Approve</button>
                                            <button type='submit' name='action' value='Reject' class='btn-reject'>Reject</button>
                                        </form>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center; padding: 30px; color: #6B7280;'>No pending payments right now.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>